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

use RKW\RkwOutcome\Domain\Model\SurveyRequest;
use RKW\RkwOutcome\Service\LogTrait;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

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
     * @inject
     */
    protected $signalSlotDispatcher;


    /**
     * @var \RKW\RkwOutcome\Domain\Repository\SurveyRequestRepository
     * @inject
     */
    protected $surveyRequestRepository;


    /**
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager
     * @inject
     */
    protected $persistenceManager;


    /**
     * @var \RKW\RkwSurvey\Domain\Repository\SurveyRepository
     * @inject
     */
    protected $surveyRepository;


    /**
     * @var \RKW\RkwOutcome\Domain\Repository\SurveyConfigurationRepository
     * @inject
     */
    protected $surveyConfigurationRepository;


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

}
