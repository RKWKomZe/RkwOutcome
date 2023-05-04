<?php
namespace RKW\RkwOutcome\Manager;

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
use RKW\RkwOutcome\Exception;
use RKW\RkwOutcome\Service\LogTrait;
use RKW\RkwRegistration\Domain\Model\FrontendUser;
use RKW\RkwSurvey\Domain\Model\Survey;
use RKW\RkwSurvey\Domain\Model\Token;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

/**
 * Class SurveyRequestManager
 *
 * @author Christian Dilger <c.dilger@addorange.de>
 * @copyright Rkw Kompetenzzentrum
 * @package RKW_RkwOutcome
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class SurveyRequestManager implements \TYPO3\CMS\Core\SingletonInterface
{

    use LogTrait;

    /**
     * Signal name for use in ext_localconf.php
     *
     * @const string
     */
    const SIGNAL_FOR_SENDING_MAIL_SURVEYREQUEST = 'sendMailSurveyRequestToUser';


    /**
     * @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
     * @inject
     */
    protected $signalSlotDispatcher;


    /**
     * @var \RKW\RkwOutcome\Domain\Repository\SurveyRequestRepository
     * @inject
     */
    protected $surveyRequestRepository;

    /**
     * @var \RKW\RkwOutcome\Domain\Repository\SurveyConfigurationRepository
     * @inject
     */
    protected $surveyConfigurationRepository;


    /**
     * @var \RKW\RkwSurvey\Domain\Repository\SurveyRepository
     * @inject
     */
    protected $surveyRepository;


    /**
     * @var \RKW\RkwSurvey\Domain\Repository\TokenRepository
     * @inject
     */
    protected $tokenRepository;


    /**
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager
     * @inject
     */
    protected $persistenceManager;


    /**
     * Intermediate function for creating surveyRequests - used by SignalSlot
     *
     * @param \RKW\RkwRegistration\Domain\Model\FrontendUser $frontendUser
     * @param \RKW\RkwShop\Domain\Model\Order|\RKW\RkwEvents\Domain\Model\EventReservation $process
     * @return void
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     */
    public function createSurveyRequestSignalSlot(FrontendUser $frontendUser, $process): void
    {
        try {
            $this->createSurveyRequest($process);
        } catch (\RKW\RkwOutcome\Exception $exception) {
            // do nothing
        }
    }


    /**
     * Creates a survey request
     *
     * @param \TYPO3\CMS\Extbase\DomainObject\AbstractEntity $process
     * @return \RKW\RkwOutcome\Domain\Model\SurveyRequest|null
     * @throws \RKW\RkwOutcome\Exception
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     */
    public function createSurveyRequest (AbstractEntity $process): ?SurveyRequest
    {
        $frontendUser = null;

        try {
            if ($this->isSurveyable($process)) {

                /** @var \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequest */
                $surveyRequest = GeneralUtility::makeInstance(SurveyRequest::class);

                if ($process instanceof \Rkw\RkwShop\Domain\Model\Order) {
                    /** @var \RKW\RkwRegistration\Domain\Model\FrontendUser $frontendUser */
                    $frontendUser = $process->getFrontendUser();
                    $surveyRequest->setOrder($process);
                }

                if ($process instanceof \Rkw\RkwEvents\Domain\Model\EventReservation) {
                    /** @var \RKW\RkwRegistration\Domain\Model\FrontendUser $frontendUser */
                    $frontendUser = $process->getFeUser();
                    $surveyRequest->setEventReservation($process);
                }

                if (!$frontendUser) {
                    throw new Exception('surveyRequestManager.error.noFrontendUser');
                }

                $surveyRequest->setProcessType(get_class($process));
                $surveyRequest->setFrontendUser($frontendUser);

                $process->getTargetGroup()->rewind();
                $surveyRequest->addTargetGroup($process->getTargetGroup()->current());

                $this->surveyRequestRepository->add($surveyRequest);
                $this->persistenceManager->persistAll();

                $this->logDebug(
                    sprintf(
                        'Created surveyRequest for process with uid=%s of type=%s by frontenduser with uid=%s',
                        $process->getUid(),
                        get_class($process),
                        $frontendUser->getUid()
                    )
                );

                return $surveyRequest;

            }
        } catch (Exception $e) {
        } catch (IllegalObjectTypeException $e) {
        } catch (InvalidQueryException $e) {
        }

        $this->logInfo(
            sprintf(
                'No surveyRequest has been created for process with uid=%s.',
                $process->getUid()
            )
        );

        return null;
    }


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

         $surveyRequestsGroupedByFrontendUser = $this->surveyRequestRepository->findPendingSurveyRequestsGroupedByFrontendUser($surveyWaitingTime, $currentTime) ;

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
     * @param \TYPO3\CMS\Extbase\DomainObject\AbstractEntity $process
     * @return bool
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     */
    protected function isSurveyable(AbstractEntity $process): bool
    {
        $notifiables = $this->getNotifiableObjects($process);

        return count($notifiables) > 0;
    }


    /**
     * @param \TYPO3\CMS\Extbase\DomainObject\AbstractEntity $process
     * @return array
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     */
    protected function getNotifiableObjects(AbstractEntity $process): array
    {
        $this->logInfo(
            sprintf(
                'Looking for configurations matching process with uid %s and targetGroup %s',
                $process->getUid(),
                json_encode($process->getTargetGroup())
            )
        );

        $notifiableObjects = [];

        if ($process instanceof \RKW\RkwShop\Domain\Model\Order) {

            /** @var \RKW\RkwShop\Domain\Model\OrderItem $orderItem */
            foreach ($process->getOrderItem() as $orderItem) {

                $this->logInfo(
                    sprintf(
                        'Looking for configurations matching orderItem with uid %s.',
                        $orderItem->getUid()
                    )
                );

                /** @var \TYPO3\CMS\Extbase\Persistence\QueryResultInterface $surveyConfigurations */
                $surveyConfigurations = $this->surveyConfigurationRepository->findByProductAndTargetGroup($orderItem->getProduct(), $process->getTargetGroup());
                if (
                    $surveyConfigurations
                    && count($surveyConfigurations->toArray()) > 0
                ) {
                    $notifiableObjects[] = $orderItem->getProduct();
                }
            }

        }

        if ($process instanceof \RKW\RkwEvents\Domain\Model\EventReservation) {

            /** @var \RKW\RkwEvents\Domain\Model\Event $event */
            $event = $process->getEvent();

            /** @var \TYPO3\CMS\Extbase\Persistence\QueryResultInterface $surveyConfigurations */
            $surveyConfigurations = $this->surveyConfigurationRepository->findByEventAndTargetGroup($event, $process->getTargetGroup());
            if (
                $surveyConfigurations
                && count($surveyConfigurations->toArray()) > 0
            ) {
                $notifiableObjects[] = $event;
            }

        }

        return $notifiableObjects;
    }


    /**
     * @param \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequest
     * @param int $currentTime
     * @return void
     *
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
     */
    protected function containsProcessableSubject(SurveyRequest $surveyRequest, AbstractEntity $processableSubject): bool
    {
        $containsProcessableSubject = false;
        $process = null;

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
    protected function isNotificationLimitReached(int $frontendUserUid, int $checkPeriod, int $maxSurveysPerPeriodAndFrontendUser, int $currentTime): bool
    {
        /** @var \TYPO3\CMS\Extbase\Persistence\QueryResultInterface $surveyRequests */
        $alreadyNotifiedRequests = $this->surveyRequestRepository->findNotifiedSurveyRequestsWithinPeriodByFrontendUser($frontendUserUid, $checkPeriod, $currentTime);

        return count($alreadyNotifiedRequests) >= $maxSurveysPerPeriodAndFrontendUser;
    }


    /**
     * @param \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequest
     * @return string
     */
    public function buildSurveyRequestTags(SurveyRequest $surveyRequest): string
    {
        $surveyRequest->getTargetGroup()->rewind();
        $targetGroupUid = $surveyRequest->getTargetGroup()->current()->getUid();

        $processSubject = ($surveyRequest->getProcessType() === 'RKW\RkwShop\Domain\Model\Order') ? $surveyRequest->getOrderSubject() : $surveyRequest->getEventReservationSubject();
        $processSubject = explode(':', $processSubject);
        $processSubject[0] = explode('\\', $processSubject[0]);
        $processSubjectType = array_pop($processSubject[0]);
        $processSubjectUid = $processSubject[1];

        $surveyRequestTags = [
            $targetGroupUid, // targetGroupUid
            $processSubjectType, // processSubjectType
            $processSubjectUid  // processSubjectUid
        ];

        return implode(',', $surveyRequestTags);
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
     */
    protected function getSurveyConfigurations(SurveyRequest $surveyRequest, AbstractEntity $processableSubject): ?QueryResultInterface
    {
        $surveyConfigurations = null;

        if (
            ($surveyRequest->getProcessType() === \RKW\RkwShop\Domain\Model\Order::class)
            && ($surveyRequest->getOrder() instanceof \RKW\RkwShop\Domain\Model\Order)
        ) {
            $surveyRequest->setOrderSubject($processableSubject);
            /** @var \TYPO3\CMS\Extbase\Persistence\QueryResultInterface $surveyConfigurations */
            $surveyConfigurations = $this->surveyConfigurationRepository->findByProductAndTargetGroup($processableSubject, $surveyRequest->getTargetGroup());
        }

        if (
            ($surveyRequest->getProcessType() === \RKW\RkwEvents\Domain\Model\EventReservation::class)
            && ($surveyRequest->getEventReservation() instanceof \RKW\RkwEvents\Domain\Model\EventReservation)
        ) {
            $surveyRequest->setEventReservationSubject($processableSubject);
            /** @var \TYPO3\CMS\Extbase\Persistence\QueryResultInterface $surveyConfigurations */
            $surveyConfigurations = $this->surveyConfigurationRepository->findByEventAndTargetGroup($processableSubject, $surveyRequest->getTargetGroup());
        }

        return $surveyConfigurations;
    }

}
