<?php
namespace RKW\RkwOutcome\SurveyRequest;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use RKW\RkwOutcome\Domain\Model\SurveyConfiguration;
use RKW\RkwOutcome\Domain\Model\SurveyRequest;
use RKW\RkwSurvey\Domain\Model\Survey;
use RKW\RkwSurvey\Domain\Model\Token;
use RKW\RkwSurvey\Domain\Repository\TokenRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException;
use TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException;

/**
 * Class SurveyRequestProcessor
 *
 * @author Christian Dilger <c.dilger@addorange.de>
 * @copyright Rkw Kompetenzzentrum
 * @package RKW_RkwOutcome
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class SurveyRequestProcessor extends AbstractSurveyRequest
{

    /**
     * Signal name for use in ext_localconf.php
     *
     * @const string
     */
    const SIGNAL_FOR_SENDING_MAIL_SURVEYREQUEST = 'sendMailSurveyRequestToUser';


    /**
     * @var \RKW\RkwSurvey\Domain\Repository\TokenRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected TokenRepository $tokenRepository;


    /**
     * Processes all pending survey requests
     *
     * @param int $checkPeriod
     * @param int $maxSurveysPerPeriodAndFrontendUser
     * @param int $surveyWaitingTime
     * @param int $currentTime
     * @return array
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     */
     public function processPendingSurveyRequests(int $checkPeriod, int $maxSurveysPerPeriodAndFrontendUser, int $surveyWaitingTime = 0, int $currentTime = 0): array
     {
         if (! $currentTime) {
             $currentTime = time();
         }

         $surveyRequestsGroupedByFrontendUser = $this->surveyRequestRepository
             ->findPendingSurveyRequestsGroupedByFrontendUser($surveyWaitingTime, $currentTime);

         $this->logInfo(
             sprintf(
                 'Get on with %s survey requests.',
                 count($surveyRequestsGroupedByFrontendUser)
             )
         );

         $notifiableSurveyRequests = [];

         foreach ($surveyRequestsGroupedByFrontendUser as $frontendUserUid => $surveyRequestsByUser) {

             if (
                 ! $this->isNotificationLimitReached($frontendUserUid, $checkPeriod, $maxSurveysPerPeriodAndFrontendUser, $currentTime)
                 && $processableSubject = $this->getProcessable($surveyRequestsByUser)
             ) {

                 /** @var \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequest */
                 foreach ($surveyRequestsByUser as $surveyRequest) {

                     if ($this->containsProcessableSubject($surveyRequest, $processableSubject)) {

                         $surveyConfigurations = $this->getSurveyConfigurations($surveyRequest, $processableSubject);
                         if (
                             $surveyConfigurations
                             && (count($surveyConfigurations->toArray()) > 0)
                         ) {

                             /** @var \RKW\RkwOutcome\Domain\Model\SurveyConfiguration $surveyConfiguration */
                             $surveyConfiguration = $surveyConfigurations->getFirst();
                             $surveyRequest->setSurveyConfiguration($surveyConfiguration);
                             $surveyRequest->setNotifiedTstamp($currentTime);

                             $generatedTokens = $this->generateTokens($surveyConfiguration);

                             $this->markAsNotified($surveyRequest, $currentTime);

                             try {
                                 $this->sendNotification($surveyRequest, $generatedTokens);

                                 /** brauchen wir das so differenziert oder genügt \Exception (mit Backslash) */
                             } catch (InvalidSlotException $e) {
                             } catch (InvalidSlotReturnException $e) {
                             }
                         }

                     } else {
                         $surveyRequest->setDeleted(true);
                     }

                     $this->surveyRequestRepository->update($surveyRequest);
                     $this->persistenceManager->persistAll();
                     $notifiableSurveyRequests[] = $surveyRequest;
                 }
             }
         }

         $this->logInfo(
             sprintf(
                 '%s pending requests have been processed.',
                 count($notifiableSurveyRequests)
             )
         );

         return $notifiableSurveyRequests;
    }


    /**
     * Send notification to request a survey from frontend user
     *
     * @param \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequest
     * @param array $generatedTokens
     * @return bool
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
     */
    public function sendNotification(SurveyRequest $surveyRequest, array $generatedTokens): bool
    {
        if ($recipient = $surveyRequest->getFrontendUser()) {

            $this->logInfo(
                sprintf(
                    'Sending notification for %s will be dispatched.',
                    $surveyRequest->getUid()
                )
            );

            // Signal for e.g. E-Mails
            $this->signalSlotDispatcher->dispatch(
                __CLASS__,
                self::SIGNAL_FOR_SENDING_MAIL_SURVEYREQUEST,
                [$recipient, $surveyRequest, $generatedTokens]
            );

            $this->logInfo(
                sprintf(
                    'Send request for survey request %s to frontend user with id %s (email %s).',
                    $surveyRequest->getUid(),
                    $recipient->getUid(),
                    $recipient->getEmail()
                )
            );

            return true;
        }

        return false;
    }


    /**
     * @param \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequest
     * @param int $currentTime
     * @return void
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     */
    protected function markAsNotified(SurveyRequest $surveyRequest, int $currentTime = 0): void
    {
        if (! $currentTime) {
            $currentTime = time();
        }

        $surveyRequest->setNotifiedTstamp($currentTime);

        $this->surveyRequestRepository->update($surveyRequest);
        $this->persistenceManager->persistAll();

        $this->logInfo(
            sprintf(
                'Survey request with uid %s has been marked as notified.',
                $surveyRequest->getUid()
            )
        );
    }


    /**
     * @param array $surveyRequestsByUser
     * @return \TYPO3\CMS\Extbase\DomainObject\AbstractEntity|null
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     */
    protected function getProcessable(array $surveyRequestsByUser): ?AbstractEntity
    {
        $notifiableObjects = [];
        foreach ($surveyRequestsByUser as $surveyRequest) {

            if ($surveyRequest->getProcessType() === \RKW\RkwShop\Domain\Model\Order::class) {
                $notifiableObjects[$surveyRequest->getUid()] = $this->getNotifiableObjects($surveyRequest->getOrder());
            }

            if ($surveyRequest->getProcessType() === \RKW\RkwEvents\Domain\Model\EventReservation::class) {
                $notifiableObjects[$surveyRequest->getUid()] = [$surveyRequest->getEventReservation()->getEvent()];
            }
        }

        $mergedNotifiableObjects = array_merge(...$notifiableObjects);
        $randomKey = array_rand($mergedNotifiableObjects);

        return (empty($mergedNotifiableObjects)) ? null : $mergedNotifiableObjects[$randomKey];
    }


    /**
     * @param \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequest
     * @param \TYPO3\CMS\Extbase\DomainObject\AbstractEntity $processableSubject
     * @return bool
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     */
    protected function containsProcessableSubject(SurveyRequest $surveyRequest, AbstractEntity $processableSubject): bool
    {
        $containsProcessableSubject = false;
        if ($surveyRequest->getProcessType() === \RKW\RkwShop\Domain\Model\Order::class) {
            $process = $surveyRequest->getOrder();
        } else {
            $process = $surveyRequest->getEventReservation();
        }

        $notifiableObjects = $this->getNotifiableObjects($process);

        if (count($notifiableObjects) > 0) {
            foreach ($notifiableObjects as $notifiableObject) {
                if ($notifiableObject->getUid() === $processableSubject->getUid()) {
                    $containsProcessableSubject = true;
                }
            }
        }

        return $containsProcessableSubject;
    }


    /**
     * @param int $frontendUserUid
     * @param int $checkPeriod
     * @param int $maxSurveysPerPeriodAndFrontendUser
     * @param int $currentTime
     * @return bool
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     */
    protected function isNotificationLimitReached(
        int $frontendUserUid,
        int $checkPeriod,
        int $maxSurveysPerPeriodAndFrontendUser,
        int $currentTime
    ): bool {

        /** @var \TYPO3\CMS\Extbase\Persistence\QueryResultInterface $surveyRequests */
        $alreadyNotifiedRequests = $this->surveyRequestRepository
            ->findNotifiedSurveyRequestsWithinPeriodByFrontendUser($frontendUserUid, $checkPeriod, $currentTime);

        return count($alreadyNotifiedRequests) >= $maxSurveysPerPeriodAndFrontendUser;
    }


    /**
     * @param \RKW\RkwOutcome\Domain\Model\SurveyConfiguration $surveyConfiguration
     * @return array
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     */
    public function generateTokens(SurveyConfiguration $surveyConfiguration): array
    {
        $generatedTokens = [];

        /* @var \RKW\RkwSurvey\Domain\Model\Survey $survey */
        foreach ($surveyConfiguration->getSurvey() as $survey) {
            if ($survey->isAccessRestricted()) {
                $tokenName = $this->generateTokenName($survey);

                /** @var \RKW\RkwSurvey\Domain\Model\Token $token */
                $token = GeneralUtility::makeInstance(Token::class);
                $token->setName($tokenName);
                $token->setPid($survey->getPid());
                $token->setUsed(true);
                $survey->addToken($token);

                $this->surveyRepository->update($survey);
                $this->persistenceManager->persistAll();

                $generatedTokens[$survey->getUid()] = $token->getName();
            }
        }

        return $generatedTokens;
    }


    /**
     * @param \RKW\RkwSurvey\Domain\Model\Survey $survey
     * @return string
     */
    protected function generateTokenName(Survey $survey): string
    {
        /** @todo see https://github.com/skroggel/typo3-core-extended/blob/master/Classes/Utility/GeneralUtility.php#L364
         * --> random_bytes — Get cryptographically secure random bytes
         * Damit sollte der generierte String zuverlässig unique sein, was Datenbankabfragen sparte
         */
        $characters = 'abcdefghjkmnopqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ';
        $newTokenName = substr(str_shuffle($characters), 0, 10);

        while ($this->tokenRepository->findOneBySurveyAndName($survey, $newTokenName)) {
            $newTokenName = substr(str_shuffle($characters), 0, 10);
        }

        return $newTokenName;
    }


    /**
     * @param \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequest
     * @param \TYPO3\CMS\Extbase\DomainObject\AbstractEntity $processableSubject
     * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface|null
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     */
    protected function getSurveyConfigurations(
        SurveyRequest $surveyRequest,
        AbstractEntity $processableSubject
    ): ?QueryResultInterface {

        $surveyConfigurations = null;
        if (
            ($surveyRequest->getProcessType() === \RKW\RkwShop\Domain\Model\Order::class)
            && ($surveyRequest->getOrder() instanceof \RKW\RkwShop\Domain\Model\Order)
        ) {
            /** CodeInspection meckert, weil Typen nicht passen */
            $surveyRequest->setOrderSubject($processableSubject);
            /** @var \TYPO3\CMS\Extbase\Persistence\QueryResultInterface $surveyConfigurations */
            $surveyConfigurations = $this->surveyConfigurationRepository
                ->findByProductAndTargetGroup($processableSubject, $surveyRequest->getTargetGroup());
        }

        if (
            ($surveyRequest->getProcessType() === \RKW\RkwEvents\Domain\Model\EventReservation::class)
            && ($surveyRequest->getEventReservation() instanceof \RKW\RkwEvents\Domain\Model\EventReservation)
        ) {
            /** CodeInspection meckert, weil Typen nicht passen */

            $surveyRequest->setEventReservationSubject($processableSubject);
            /** @var \TYPO3\CMS\Extbase\Persistence\QueryResultInterface $surveyConfigurations */
            $surveyConfigurations = $this->surveyConfigurationRepository
                ->findByEventAndTargetGroup($processableSubject, $surveyRequest->getTargetGroup());
        }

        return $surveyConfigurations;
    }

}
