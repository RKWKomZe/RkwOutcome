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

use Madj2k\Accelerator\Persistence\MarkerReducer;
use RKW\RkwOutcome\Domain\Repository\SurveyConfigurationRepository;
use RKW\RkwOutcome\Domain\Repository\SurveyRequestRepository;
use RKW\RkwOutcome\Log\LogTrait;
use RKW\RkwSurvey\Domain\Repository\SurveyRepository;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

/**
 * Class AbstractSurveyRequest
 *
 * @author Christian Dilger <c.dilger@addorange.de>
 * @copyright Rkw Kompetenzzentrum
 * @package RKW_RkwOutcome
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
abstract class AbstractSurveyRequest implements \TYPO3\CMS\Core\SingletonInterface
{
    use LogTrait;

    /**
     * @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected ?Dispatcher $signalSlotDispatcher;


    /**
     * @var \RKW\RkwOutcome\Domain\Repository\SurveyRequestRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected ?SurveyRequestRepository $surveyRequestRepository;


    /**
     * @var \RKW\RkwOutcome\Domain\Repository\SurveyConfigurationRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected ?SurveyConfigurationRepository $surveyConfigurationRepository;


    /**
     * @var \RKW\RkwSurvey\Domain\Repository\SurveyRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected ?SurveyRepository $surveyRepository;


    /**
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected ?PersistenceManager $persistenceManager;


    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager|null
     */
    protected ?ObjectManager $objectManager = null;


    /**
     * @var \Madj2k\Accelerator\Persistence\MarkerReducer|null
     */
    protected ?MarkerReducer $markerReducer = null;


    /**
     * @param \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher
     */
    public function injectDispatcher(Dispatcher $signalSlotDispatcher)
    {
        $this->signalSlotDispatcher = $signalSlotDispatcher;
    }


    /**
     * @param \RKW\RkwOutcome\Domain\Repository\SurveyRequestRepository $surveyRequestRepository
     */
    public function injectSurveyRequestRepository(SurveyRequestRepository $surveyRequestRepository)
    {
        $this->surveyRequestRepository = $surveyRequestRepository;
    }


    /**
     * @param \RKW\RkwOutcome\Domain\Repository\SurveyConfigurationRepository $surveyConfigurationRepository
     */
    public function injectSurveyConfigurationRepository(SurveyConfigurationRepository $surveyConfigurationRepository)
    {
        $this->surveyConfigurationRepository = $surveyConfigurationRepository;
    }


    /**
     * @param \RKW\RkwSurvey\Domain\Repository\SurveyRepository $surveyRepository
     */
    public function injectSurveyRepository(SurveyRepository $surveyRepository)
    {
        $this->surveyRepository = $surveyRepository;
    }


    /**
     * @param \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager $persistenceManager
     */
    public function injectPersistenceManager(PersistenceManager $persistenceManager)
    {
        $this->persistenceManager = $persistenceManager;
    }


    /**
     * @return void
     * @throws \TYPO3\CMS\Extbase\Object\Exception
     */
    public function __construct()
    {
        /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);

        /** @var \Madj2k\Accelerator\Persistence\MarkerReducer $markerReducer */
        $this->markerReducer = $this->objectManager->get(MarkerReducer::class);
    }


    /**
     * @param \TYPO3\CMS\Extbase\DomainObject\AbstractEntity $process
     * @return array
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     */
    protected function getNotifiableObjects(AbstractEntity $process): array
    {
        $notifiableObjects = [];

        if ($process instanceof \RKW\RkwShop\Domain\Model\Order) {

            $this->logInfo(
                sprintf(
                    'Looking for configurations matching orderItems in order %s.',
                    $process->getUid()
                )
            );

            /** @var \RKW\RkwShop\Domain\Model\OrderItem $orderItem */
            foreach ($process->getOrderItem() as $orderItem) {

                /** @var \TYPO3\CMS\Extbase\Persistence\QueryResultInterface $surveyConfigurations */
                $surveyConfigurations = $this->surveyConfigurationRepository->findByProductAndTargetGroup(
                    $orderItem->getProduct(),
                    $process->getTargetGroup()
                );

                if (
                    $surveyConfigurations
                    && count($surveyConfigurations->toArray()) > 0
                ) {
                    $notifiableObjects[] = $orderItem->getProduct();
                }
            }

        }

        if ($process instanceof \RKW\RkwEvents\Domain\Model\EventReservation) {

            $this->logInfo(
                sprintf(
                    'Looking for configurations matching event in reservation %s.',
                    $process->getUid()
                )
            );

            /** @var \RKW\RkwEvents\Domain\Model\Event $event */
            $event = $process->getEvent();

            /** @var \TYPO3\CMS\Extbase\Persistence\QueryResultInterface $surveyConfigurations */
            $surveyConfigurations = $this->surveyConfigurationRepository->findByEventAndTargetGroup(
                $event,
                $process->getTargetGroup()
            );
            if (
                $surveyConfigurations
                && count($surveyConfigurations->toArray()) > 0
            ) {
                $notifiableObjects[] = $event;
            }

        }

        return $notifiableObjects;
    }

}
