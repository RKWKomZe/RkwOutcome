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

use RKW\RkwOutcome\Domain\Model\SurveyRequest;
use RKW\RkwOutcome\Service\LogTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
     */
    public function createSurveyRequestSignalSlot(\RKW\RkwRegistration\Domain\Model\FrontendUser $frontendUser, $process): void
    {
        try {
            $this->createSurveyRequest($process);
        } catch (\RKW\RkwOutcome\Exception $exception) {
            // do nothing
        }
    }


    /**
     * createSurveyRequest
     *
     * @param \TYPO3\CMS\Extbase\DomainObject\AbstractEntity $process
     * @return \RKW\RkwOutcome\Domain\Model\SurveyRequest|null
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     */
    public function createSurveyRequest (\TYPO3\CMS\Extbase\DomainObject\AbstractEntity $process): ?SurveyRequest
    {
        /** @var \RKW\RkwRegistration\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = (method_exists($process, 'getFrontendUser')) ? $process->getFrontendUser() : $process->getFeUser();

        if ($this->isSurveyable($process)) {

            /** @var \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequest */
            $surveyRequest = GeneralUtility::makeInstance(SurveyRequest::class);

            if ($process instanceof \Rkw\RkwShop\Domain\Model\Order) {
                $surveyRequest->setOrder($process);
            }

            if ($process instanceof \Rkw\RkwEvents\Domain\Model\EventReservation) {
                $surveyRequest->setEventReservation($process);
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

        $this->logInfo(
            sprintf(
                'No surveyRequest has been created for process with uid=%s by frontenduser with %s.',
                $process->getUid(),
                $frontendUser->getUid()
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
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     */
     public function processPendingSurveyRequests(int $checkPeriod, int $maxSurveysPerPeriodAndFrontendUser, int $surveyWaitingTime = 0, int $currentTime = 0):array {

         $surveyRequestsGroupedByFrontendUser = $this->surveyRequestRepository->findAllPendingSurveyRequestsGroupedByFrontendUser($surveyWaitingTime, $currentTime) ;

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

                         if ($surveyRequest->getProcessType() === \RKW\RkwShop\Domain\Model\Order::class) {
                             // @todo: zusätzlich prüfen auf instanceOf Order
                             $surveyRequest->setOrderSubject($processableSubject);
                             /** @var \TYPO3\CMS\Extbase\Persistence\QueryResultInterface */
                             $surveyConfigurations = $this->surveyConfigurationRepository->findByProductAndTargetGroup($processableSubject, $surveyRequest->getTargetGroup());
                         }

                         if ($surveyRequest->getProcessType() === \RKW\RkwEvents\Domain\Model\EventReservation::class) {
                             // @todo: zusätzlich prüfen auf instanceOf EventReservation
                             $surveyRequest->setEventReservationSubject($processableSubject);
                             /** @var \TYPO3\CMS\Extbase\Persistence\QueryResultInterface */
                             $surveyConfigurations = $this->surveyConfigurationRepository->findByEventAndTargetGroup($processableSubject, $surveyRequest->getTargetGroup());
                         }

                         // @todo: Abfangen bei leerem Resultset $surveyConfigurations -> Test schreiben
                         /** @var \RKW\RkwOutcome\Domain\Model\SurveyConfiguration $surveyConfiguration */
                         $surveyConfiguration = $surveyConfigurations->getFirst();
                         $surveyRequest->setSurvey($surveyConfiguration->getSurvey());
                         $this->surveyRequestRepository->update($surveyRequest);

                         $this->logInfo(
                             sprintf(
                                 'Pending request %s with process_subject %s will be notified.',
                                 $surveyRequest->getUid(),
                                 $processableSubject->getUid()
                             )
                         );

                         $this->sendNotification($surveyRequest);

                     }

                     $notifiableSurveyRequests[] = $surveyRequest;

                 }

                 foreach ($notifiableSurveyRequests as $notifiedSurveyRequest) {
                     if (! $currentTime) {
                         $currentTime = time();
                     }
                     $this->markAsNotified($notifiedSurveyRequest, $currentTime);
                 }

             }

         }

         $this->logInfo(
             sprintf(
                 '%s pending request have been processed.',
                 count($notifiableSurveyRequests)
             )
         );

         return $notifiableSurveyRequests;

    }


    /**
     * Send notification to request a survey from frontend user
     *
     * @param \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequest
     * @return bool
    */
    public function sendNotification(\RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequest): bool
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
                [$recipient, $surveyRequest]
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
     */
    protected function isSurveyable(\TYPO3\CMS\Extbase\DomainObject\AbstractEntity $process): bool
    {

        return count($this->getNotifiableObjects($process)) > 0;

    }


    /**
     * @param \TYPO3\CMS\Extbase\DomainObject\AbstractEntity $process
     * @return array
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     */
    protected function getNotifiableObjects(\TYPO3\CMS\Extbase\DomainObject\AbstractEntity $process): array
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

                //  @todo: Heads up! QueryResultInterface kann bei count() evtl. einen Fehler liefern. Fallback toArray().
                /** @var \TYPO3\CMS\Extbase\Persistence\QueryResultInterface */
                $result = $this->surveyConfigurationRepository->findByProductAndTargetGroup($orderItem->getProduct(), $process->getTargetGroup());
                if (
                    $result->count() > 0
                ) {
                    $notifiableObjects[] = $orderItem->getProduct();
                }
            }

        }

        if ($process instanceof \RKW\RkwEvents\Domain\Model\EventReservation) {

            /** @var \RKW\RkwEvents\Domain\Model\Event $event */
            $event = $process->getEvent();

            /** @var \RKW\RkwOutcome\Domain\Model\SurveyConfiguration $surveyConfiguration */
            if (
                $this->surveyConfigurationRepository->findByEventAndTargetGroup($event, $process->getTargetGroup())
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
     */
    protected function getProcessable(array $surveyRequestsByUser): ?\TYPO3\CMS\Extbase\DomainObject\AbstractEntity
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
     * @param SurveyRequest $surveyRequest
     * @param \TYPO3\CMS\Extbase\DomainObject\AbstractEntity $processableSubject
     * @return bool
     */
    protected function containsProcessableSubject(SurveyRequest $surveyRequest, \TYPO3\CMS\Extbase\DomainObject\AbstractEntity $processableSubject): bool
    {
        $containsProcessableSubject = false;

        if ($surveyRequest->getProcessType() === \RKW\RkwShop\Domain\Model\Order::class) {
            $process = $surveyRequest->getOrder();
        } else {
            $process = $surveyRequest->getEventReservation();
        }

        foreach ($this->getNotifiableObjects($process) as $notifiableObject) {
            if ($notifiableObject->getUid() === $processableSubject->getUid()) {
                $containsProcessableSubject = true;
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
        $alreadyNotifiedRequests = $this->surveyRequestRepository->findAllNotifiedSurveyRequestsWithinPeriodByFrontendUser($frontendUserUid, $checkPeriod, $currentTime);

        return count($alreadyNotifiedRequests) >= $maxSurveysPerPeriodAndFrontendUser;
    }

}
