<?php
namespace RKW\RkwOutcome\Utility;
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
use RKW\RkwEvents\Domain\Model\Event;
use RKW\RkwEvents\Domain\Repository\EventRepository;
use RKW\RkwOutcome\Domain\Repository\SurveyConfigurationRepository;
use RKW\RkwOutcome\Domain\Repository\SurveyRequestRepository;
use RKW\RkwShop\Domain\Model\Product;
use RKW\RkwShop\Domain\Repository\ProductRepository;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * TCA
 *
 * @author Christian Dilger <c.dilger@addorange.de>
 * @copyright Rkw Kompetenzzentrum
 * @package RKW_RkwOutcome
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class TCA
{

    /**
     * @var \RKW\RkwOutcome\Domain\Repository\SurveyConfigurationRepository
     */
    protected ?SurveyConfigurationRepository $surveyConfigurationRepository;


    /**
     * @var \RKW\RkwOutcome\Domain\Repository\SurveyRequestRepository
     */
    protected ?SurveyRequestRepository $surveyRequestRepository;


    /**
     * @var \RKW\RkwShop\Domain\Repository\ProductRepository
     */
    protected ?ProductRepository $productRepository;


    /**
     * @var \RKW\RkwEvents\Domain\Repository\EventRepository
     */
    protected ?EventRepository $eventRepository;


    /**
     * @return void
     * @throws \TYPO3\CMS\Extbase\Object\Exception
     */
    public function __construct()
    {

        /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);

        /** @var \RKW\RkwOutcome\Domain\Repository\SurveyRequestRepository $surveyRequestRepository */
        $this->surveyRequestRepository = $objectManager->get(SurveyRequestRepository::class);

        /** @var \RKW\RkwOutcome\Domain\Repository\SurveyConfigurationRepository $surveyConfigurationRepository */
        $this->surveyConfigurationRepository = $objectManager->get(SurveyConfigurationRepository::class);

        /** @var \RKW\RkwShop\Domain\Repository\ProductRepository $productRepository */
        $this->productRepository = $objectManager->get(ProductRepository::class);

        /** @var \RKW\RkwEvents\Domain\Repository\EventRepository $eventRepository */
        $this->eventRepository = $objectManager->get(EventRepository::class);

    }


    /**
     * @param array $parameters
     * @return void
     */
    public function surveyConfigurationTitle(array &$parameters): void
    {

        $record = BackendUtility::getRecord($parameters['table'], $parameters['row']['uid']);

        /** @var \RKW\RkwOutcome\Domain\Model\SurveyConfiguration $surveyConfiguration */
        $surveyConfiguration = $this->surveyConfigurationRepository->findByUid($record['uid']);

        $targetGroups = [];

        if ($surveyConfiguration) {
            foreach ($surveyConfiguration->getTargetGroup() as $targetGroup) {
                $targetGroups[] = $targetGroup->getTitle();
            }
        }

        $newTitle = '';

        if ($record['process_type'] === Product::class && $record['product']) {
            /** @var \RKW\RkwShop\Domain\Model\Product $product */
            $product = $this->productRepository->findByUid($record['product']);
            $newTitle = sprintf(
                '[Produkt - %s] %s (%s)',
                $record['product'],
                $product->getTitle(),
                implode(', ', $targetGroups),
            );
        }

        if ($record['process_type'] === Event::class && $record['event']) {
            /** @var \RKW\RkwEvents\Domain\Model\Event $event */
            $event = $this->eventRepository->findByUid($record['event']);
            $newTitle = sprintf(
                '[Veranstaltung - %s] %s (%s)',
                $record['event'],
                $event->getSeries()->getTitle(),
                implode(', ', $targetGroups),
            );
        }

        $parameters['title'] = $newTitle;

    }


    public function surveyRequestTitle(&$parameters): void
    {

        $record = BackendUtility::getRecord($parameters['table'], $parameters['row']['uid']);

        /** @var \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequest */
        $surveyRequest = $this->surveyRequestRepository->findByUid($record['uid']);

        $process = MarkerReducer::explode($surveyRequest->getProcess())['process'];

        if ($process instanceof \RKW\RkwShop\Domain\Model\Order) {
            $newTitle = sprintf(
                '[Bestellung - %s (%s)] %s',
                $process->getUid(),
                date('d.m.Y H:i', $process->getShippedTstamp()),
                $process->getFrontendUser()->getEmail(),
            );
        }

        if ($process instanceof \RKW\RkwEvents\Domain\Model\EventReservation) {
            $newTitle = '[Reservierung] ' . $process->getUid();
        }

        if ($surveyRequest->getNotifiedTstamp() > 0) {
            $newTitle = $newTitle . ' (' . date('d.m.Y H:i', $surveyRequest->getNotifiedTstamp()) . ')';
        }

        $parameters['title'] = $newTitle;
    }

}
