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
use RKW\RkwOutcome\Domain\Repository\SurveyConfigurationRepository;
use RKW\RkwOutcome\Domain\Repository\SurveyRequestRepository;
use RKW\RkwOutcome\Service\LogTrait;
use RKW\RkwSurvey\Domain\Repository\SurveyRepository;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;

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
    protected Dispatcher $signalSlotDispatcher;


    /**
     * @var \RKW\RkwOutcome\Domain\Repository\SurveyRequestRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected SurveyRequestRepository $surveyRequestRepository;


    /**
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected PersistenceManager $persistenceManager;


    /**
     * @var \RKW\RkwSurvey\Domain\Repository\SurveyRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected SurveyRepository $surveyRepository;


    /**
     * @var \RKW\RkwOutcome\Domain\Repository\SurveyConfigurationRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected SurveyConfigurationRepository $surveyConfigurationRepository;


    /**
     * @param \TYPO3\CMS\Extbase\DomainObject\AbstractEntity $process
     * @return array
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     */
    protected function getNotifiableObjects(AbstractEntity $process): array
    {

        /** @todo Die code-inspection weist darauf hin, dass getTargetGroup nicht als Methode existiert, vermutlich weil
         * ich bei mir die Änderungen an der rkw_shop und rkw_events nicht habe.
         * Vielleicht braucht man dann doch eine Kapsel-Klasse, die die TargetGroup enthält und
         * den Klassennamen und die uid der Referenzklasse. Bin dafür aber nicht tief genug in der Logik drin, sodass
         * ich auch nicht sagen kann, wie die TargetGroup dann zu setzen wäre.
         */
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

                /** @todo Die code-inspection weist darauf hin, dass getTargetGroup nicht als Methode existiert */
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

            /** @var \RKW\RkwEvents\Domain\Model\Event $event */
            $event = $process->getEvent();

            /** @todo Die code-inspection weist darauf hin, dass getTargetGroup nicht als Methode existiert */
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
