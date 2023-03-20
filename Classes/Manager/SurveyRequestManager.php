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
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * SurveyRequestManager
 *
 * @author Christian Dilger <c.dilger@addorange.de>
 * @copyright Rkw Kompetenzzentrum
 * @package RKW_RkwOutcome
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class SurveyRequestManager implements \TYPO3\CMS\Core\SingletonInterface
{

    /**
     * Signal name for use in ext_localconf.php
     *
     * @const string
     */
    const SIGNAL_FOR_SENDING_MAIL_SURVEYREQUEST = 'sendMailSurveyRequestToUser';

    /**
     * Signal name for use in ext_localconf.php
     *
     * @const string
     */
    const SIGNAL_AFTER_ORDER_CREATED_USER = 'afterOrderCreatedUser';

    /**
     * Signal Slot Dispatcher
     *
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
     * PersistenceManager
     *
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager
     * @inject
     */
    protected $persistenceManager;

    /**
     * @var \TYPO3\CMS\Core\Log\Logger
     */
    protected $logger;


    /**
     * Intermediate function for creating surveyRequests - used by SignalSlot
     *
     * @param \RKW\RkwRegistration\Domain\Model\FrontendUser|array $frontendUser
     * @param \RKW\RkwShop\Domain\Model\Order $process
     * @return void
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
     */
    public function createSurveyRequestSignalSlot
    (
//        \RKW\RkwRegistration\Domain\Model\FrontendUser $frontendUser,
        $frontendUser,
        $process
    ): void
    {

        //  @todo: Use exceptions
        $this->createSurveyRequest($process);

//        try {
//            $this->createSurveyRequest($process);
//        } catch (\RKW\RkwShop\Exception $exception) {
//            // do nothing
//        }

    }

    /**
     * Intermediate function for creating survey requests - used by SignalSlot
     *
     * @param \RKW\RkwShop\Domain\Model\Order $process
     * @return \RKW\RkwOutcome\Domain\Model\SurveyRequest|null
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     */
    public function createSurveyRequest
    (
        \RKW\RkwShop\Domain\Model\Order $process
    ): ?SurveyRequest
    {
        //  @todo: use a custom Signal in OrderManager->saveOrder to provide FE-User instead of BE-User
        //  @todo: Alternativ: Wie kann ich $frontendUser und $backendUserForProductMap ignorieren?


        //  @todo: Lässt sich das auch über einen Accessor o. ä. lösen?
        /** @var \RKW\RkwRegistration\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = (method_exists($process, 'getFrontendUser')) ? $process->getFrontendUser() : $process->getFeUser();

        $this->getLogger()->log(
            LogLevel::INFO,
            sprintf(
                'Created surveyRequest for process with uid=%s of by frontenduser with %s.',
                $process->getUid(),
                $frontendUser->getUid()
            )
        );

        //  @todo: only surveyable, if same target group
        if ($this->isSurveyable($process)) {

            /** @var \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequest */
            $surveyRequest = GeneralUtility::makeInstance(SurveyRequest::class);
            $surveyRequest->setProcess($process);
            $surveyRequest->setProcessType(get_class($process));
            $surveyRequest->setFrontendUser($frontendUser);

            //  @todo: TargetGroups über die sys_categories steuern
            //  @todo: targetGroup must be mandatory in order form, otherwise this next condition crashes:
            //  Argument 1 passed to RKW\RkwOutcome\Domain\Model\SurveyRequest::setTargetGroup() must be an instance of RKW\RkwBasics\Domain\Model\TargetGroup, null given
            $surveyRequest->setTargetGroup($process->getTargetGroup());

            $this->surveyRequestRepository->add($surveyRequest);
            $this->persistenceManager->persistAll();

            $this->getLogger()->log(
                LogLevel::DEBUG,
                sprintf(
//                'Created surveyRequest for order with id=%s of by frontenduser with id=%s.',
                    'Created surveyRequest for process with id=%s of type=%s by frontenduser with id=',
                    $process->getUid(),
                    get_class($process)
//                $issue->getUid()
                )
            );

            return $surveyRequest;

        }

        return null;

    }


    /**
     * Processes all pending survey requests
     *
     * @param int $tolerance
     * @param int $timestampNow
     * @return array
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     * @throws \TYPO3\CMS\Core\Type\Exception\InvalidEnumerationValueException
     */
     public function processPendingSurveyRequests(int $tolerance = 0, int $timestampNow = 0):array {

         /** @var  \TYPO3\CMS\Extbase\Persistence\QueryResultInterface $surveyRequests */
         $surveyRequests = $this->surveyRequestRepository->findAllPendingSurveyRequests();

         $this->getLogger()->log(
             LogLevel::INFO,
             sprintf(
                 'Get on with %s survey requests.',
                 $surveyRequests->count()
             )
         );

         $notifiedSurveyRequests = [];

         /** @var \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequest */
         foreach ($surveyRequests as $surveyRequest) {

             $this->getLogger()->log(
                 LogLevel::INFO,
                 sprintf(
                     'Get on with survey request with uid %s.',
                     $surveyRequest->getUid()
                 )
             );

             if ($surveyRequest->getProcess()) {
                 if ($this->isNotifiable($surveyRequest->getProcess())) {

                     $this->getLogger()->log(
                         LogLevel::INFO,
                         sprintf(
                             'Survey request with uid %s is notifiable.',
                             $surveyRequest->getUid()
                         )
                     );

                     if ($this->sendNotification($surveyRequest)) {
                         $this->markAsNotified($surveyRequest);

                         $notifiedSurveyRequests[] = $surveyRequest;
                     }

                 }

             }
         }

         $this->getLogger()->log(
             LogLevel::INFO,
             sprintf(
                 '%s pending request have been processed.',
                 count($notifiedSurveyRequests)
             )
         );

        return $notifiedSurveyRequests;

    }


    /**
     * Send notification to request a survey from frontend user
     *
     * @param \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequest
    */
    public function sendNotification(\RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequest)
    {

        if ($recipient = $surveyRequest->getFrontendUser()) {

            // Signal for e.g. E-Mails
            $this->signalSlotDispatcher->dispatch(
                __CLASS__,
                self::SIGNAL_FOR_SENDING_MAIL_SURVEYREQUEST,
                [$recipient, $surveyRequest]
            );

            $this->getLogger()->log(
                LogLevel::INFO,
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
     * Checks, if process is associated with a valid survey
     *
     * @param \RKW\RkwShop\Domain\Model\Order $process
     * @return bool
     */
    protected function isNotifiable(\RKW\RkwShop\Domain\Model\Order $process): bool
    {

        $notifiableObjects = [];

        //  check contained products
        if ($process instanceof \RKW\RkwShop\Domain\Model\Order) {

            $shippedTstamp = $process->getShippedTstamp();

            if (
                $shippedTstamp > 0
                && $shippedTstamp < time() // @todo: Plus SurveyWaitingTime, ...
            ) {
                /** @var \RKW\RkwShop\Domain\Model\OrderItem $orderItem */
                foreach ($process->getOrderItem() as $orderItem) {
                    /** @var \RKW\RkwOutcome\Domain\Model\Survey $survey */
                    if (
                        ($survey = $this->surveyConfigurationRepository->findByProductUid($orderItem->getProduct()))
                        && $survey->getTargetGroup() === $process->getTargetGroup()
                    ) {
                        $notifiableObjects[] = $orderItem->getProduct();
                    }
                }
            }

        }
        //  check contained products
        if ($process instanceof \RKW\RkwEvents\Domain\Model\EventReservation) {

            /** @var \RKW\RkwEvents\Domain\Model\Event $event */
            $event = $process->getEvent();
            $endTstamp = $event->getEnd();

            if (
                $endTstamp > 0
                && $endTstamp < time() // @todo: Plus SurveyWaitingTime, ...
            ) {

                /** @var \RKW\RkwOutcome\Domain\Model\Survey $survey */
                if (
                    ($survey = $this->surveyConfigurationRepository->findByEventUid($event->getUid()))
                    && $survey->getTargetGroup() === $process->getTargetGroup()
                ) {
                    $notifiableObjects[] = $event;
                }

            }

        }

        return count($notifiableObjects) > 0;


    }

    /**
     * Checks, if process is associated with a valid survey
     *
     * @param \RKW\RkwShop\Domain\Model\Order $process
     * @return bool
     */
    protected function isSurveyable(\RKW\RkwShop\Domain\Model\Order $process): bool
    {
        $isSurveyable = false;
        $surveyableObjects = [];

        //  check contained products
        if ($process instanceof \RKW\RkwShop\Domain\Model\Order) {

            $this->getLogger()->log(
                LogLevel::INFO,
                sprintf(
                    'Check surveyable for order uid %s.',
                    $process->getUid()
                )
            );

            /** @var \RKW\RkwShop\Domain\Model\OrderItem $orderItem */
            foreach ($process->getOrderItem() as $orderItem) {

                /** @var \RKW\RkwOutcome\Domain\Model\Survey $survey */
                $survey = $this->surveyConfigurationRepository->findByProductUid($orderItem->getProduct());

                /** @var \RKW\RkwOutcome\Domain\Model\Survey $survey */
                if (
                    ($survey = $this->surveyConfigurationRepository->findByProductUid($orderItem->getProduct()))
//                    && $survey->getTargetGroup() === $process->getTargetGroup()
                    //  @todo: Fix this ... notwendig, da derzeit noch die Bestellung lediglich ein Textfeld mit der Zielgruppe befüllt!!!
                    && $survey->getTargetGroup()->getUid() === $process->getTargetGroup()->getUid()
                ) {
                    $surveyableObjects[] = $orderItem->getProduct();
                }
            }

        }
        //  check contained products
        if ($process instanceof \RKW\RkwEvents\Domain\Model\EventReservation) {

            /** @var \RKW\RkwEvents\Domain\Model\Event $event */
            $event = $process->getEvent();

            /** @var \RKW\RkwOutcome\Domain\Model\Survey $survey */
            if (
                ($survey = $this->surveyConfigurationRepository->findByEventUid($event->getUid()))
                && $survey->getTargetGroup() === $process->getTargetGroup()
            ) {
                $surveyableObjects[] = $event;
            }

        }

        $isSurveyable = count($surveyableObjects) > 0;

        $isStringSurveyable = ($isSurveyable) ? 'true' : 'false';

        $this->getLogger()->log(
            LogLevel::INFO,
            sprintf(
                'Process with uid %s is surveyable %s.',
                $process->getUid(),
                $isStringSurveyable
            )
        );

        return $isSurveyable;
    }


    /**
     * @param SurveyRequest $surveyRequest
     * @return void
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     */
    protected function markAsNotified(SurveyRequest $surveyRequest): void
    {

        //  @todo select product or event
        $process = $surveyRequest->getProcess();

        if ($process instanceof \RKW\RkwShop\Domain\Model\Order) {
            $process->getOrderItem()->rewind();
            $surveyRequest->setProcessSubject($process->getOrderItem()->current()->getProduct());
        } else {
            $surveyRequest->setProcessSubject($process->getEvent());
        }

        $surveyRequest->setNotifiedTstamp(time());

        $this->surveyRequestRepository->update($surveyRequest);
        $this->persistenceManager->persistAll();

        $this->getLogger()->log(
            LogLevel::INFO,
            sprintf(
                'Survey request with uid %s has been marked as notified.',
                $surveyRequest->getUid()
            )
        );

    }


    /**
     * Returns logger instance
     *
     * @return \TYPO3\CMS\Core\Log\Logger
     */
    protected function getLogger(): Logger
    {
        if (!$this->logger instanceof Logger) {
            $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
        }

        return $this->logger;
    }

}