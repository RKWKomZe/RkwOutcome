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
     * @const int
     */
    const RANDOM_STRING_LENGTH = 30;


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
     * @param int $currentTime
     * @return array
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     */
     public function processPendingSurveyRequests(
         int $checkPeriod,
         int $maxSurveysPerPeriodAndFrontendUser,
         int $currentTime = 0
     ): array {

         if (! $currentTime) {
             $currentTime = time();
         }

         $surveyRequestsGroupedByFrontendUser = $this->surveyRequestRepository
             ->findPendingSurveyRequestsGroupedByFrontendUser($currentTime);

         $this->logInfo(
             sprintf(
                 'Found %s possible survey requests.',
                 count($surveyRequestsGroupedByFrontendUser)
             )
         );

         $notifiableSurveyRequests = [];

         foreach ($surveyRequestsGroupedByFrontendUser as $frontendUserUid => $surveyRequestsByUser) {

             if (
                 ! $this->isNotificationLimitReached($frontendUserUid, $checkPeriod, $maxSurveysPerPeriodAndFrontendUser, $currentTime)
                 && $processableSubject = $this->getRandomProcessableSubject($surveyRequestsByUser)
             ) {

                 /** @var \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequest */
                 foreach ($surveyRequestsByUser as $surveyRequest) {

                     if (
                         ($this->containsProcessableSubject($surveyRequest, $processableSubject))
                         && ($surveyRequest->getFrontendUser()->getTxFeregisterConsentMarketing())
                     ) {

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
                                 /** @var \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequest */
                                 $surveyRequest = $this->surveyRequestRepository->findByUid($surveyRequest->getUid());

                                 $this->sendNotification($surveyRequest, $generatedTokens);

                             } catch (\Exception $e) {
                                 // do nothing
                             }

                             $notifiableSurveyRequests[] = $surveyRequest;
                             $this->surveyRequestRepository->update($surveyRequest);

                         }

                     } else {
                         $this->surveyRequestRepository->remove($surveyRequest);
                     }

                     $this->persistenceManager->persistAll();
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

            // Signal for e.g. E-Mails
            $this->signalSlotDispatcher->dispatch(
                __CLASS__,
                self::SIGNAL_FOR_SENDING_MAIL_SURVEYREQUEST,
                [$recipient, $surveyRequest, $generatedTokens]
            );

            $this->logInfo(
                sprintf(
                    'Sending notification for survey request %s to frontend user with id %s (email %s).',
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
     */
    protected function getRandomProcessableSubject(array $surveyRequestsByUser): ?AbstractEntity
    {
        $notifiableObjects = [];
        foreach ($surveyRequestsByUser as $surveyRequest) {
            $process = $this->markerReducer->explode($surveyRequest->getProcess())['process'];
            $notifiableObjects[$surveyRequest->getUid()] = $this->getNotifiableObjects($process);
        }

        $mergedNotifiableObjects = array_merge(...$notifiableObjects);

        if (empty($mergedNotifiableObjects)) {
            return null;
        }

        $randomKey = array_rand($mergedNotifiableObjects);
        return $mergedNotifiableObjects[$randomKey];
    }


    /**
     * @param \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequest
     * @param \TYPO3\CMS\Extbase\DomainObject\AbstractEntity $processableSubject
     * @return bool
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     */
    protected function containsProcessableSubject(SurveyRequest $surveyRequest, AbstractEntity $processableSubject): bool
    {
        $containsProcessableSubject = false;
        $process = $this->markerReducer->explode($surveyRequest->getProcess())['process'];

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
     * @throws \Exception
     */
    protected function generateTokenName(Survey $survey): string
    {

        /** @todo check if this is enough */
        return \Madj2k\CoreExtended\Utility\GeneralUtility::getUniqueRandomString();

        $bytes = random_bytes(self::RANDOM_STRING_LENGTH / 2);
        $newTokenName = bin2hex($bytes);

        while ($this->tokenRepository->findOneBySurveyAndName($survey, $newTokenName)) {
            $newTokenName = bin2hex($bytes);
        }

        return $newTokenName;
    }


    /**
     * @param \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequest
     * @param \TYPO3\CMS\Extbase\DomainObject\AbstractEntity $processableSubject
     * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface|null
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     */
    protected function getSurveyConfigurations(
        SurveyRequest $surveyRequest,
        AbstractEntity $processableSubject
    ): ?QueryResultInterface {

        $surveyConfigurations = null;

        $process = $this->markerReducer->explode($surveyRequest->getProcess())['process'];
        if ($process instanceof \RKW\RkwShop\Domain\Model\Order) {
            /** @var \TYPO3\CMS\Extbase\Persistence\QueryResultInterface $surveyConfigurations */
            $surveyConfigurations = $this->surveyConfigurationRepository
                ->findByProductAndTargetGroup($processableSubject, $surveyRequest->getTargetGroup());
        }

        if ($process instanceof \RKW\RkwEvents\Domain\Model\EventReservation) {
            /** @var \TYPO3\CMS\Extbase\Persistence\QueryResultInterface $surveyConfigurations */
            $surveyConfigurations = $this->surveyConfigurationRepository
                ->findByEventAndTargetGroup($processableSubject, $surveyRequest->getTargetGroup());
        }

        return $surveyConfigurations;
    }

}
