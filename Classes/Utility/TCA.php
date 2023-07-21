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
use RKW\RkwOutcome\Domain\Repository\SurveyRequestRepository;
use RKW\RkwShop\Domain\Model\Product;
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
     * @var \RKW\RkwOutcome\Domain\Repository\SurveyRequestRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected SurveyRequestRepository $surveyRequestRepository;


    /**
     * @var \Madj2k\Accelerator\Persistence\MarkerReducer|null
     */
    protected ?MarkerReducer $markerReducer = null;


    /**
     * @param array $parameters
     * @return void
     */
    public function surveyConfigurationTitle(array &$parameters): void
    {

        $record = BackendUtility::getRecord($parameters['table'], $parameters['row']['uid']);
        /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);

        //  @todo: Fix trouble with external table not found. Example: #1472074485: Table 'rkw_komze_dev.tx_rkwshop_domain_model_author' doesn't exist
        if ($record['process_type'] === Product::class) {
//            $productRepository = $objectManager->get(ProductRepository::class);
//            /** @var \RKW\RkwShop\Domain\Model\Product $product */
//            $product = $productRepository->findByUid($record['product']);
//            $newTitle = sprintf(
//                '%s - ',
//                $product->getTitle()
//            );
            $newTitle = '[Product] ' . $record['product'] . ' (' .  $record['target_group'] . ')';
        }

        if ($record['process_type'] === Event::class) {
//            $eventRepository = $objectManager->get(EventRepository::class);
//            /** @var \RKW\RkwEvents\Domain\Model\Event $event */
//            $event = $eventRepository->findByUid($record['event']);
//            $newTitle = sprintf(
//                '%s - ',
//                $event->getTitle()
//            );
            $newTitle = '[Event] ' . $record['event'] . ' (' .  $record['target_group'] . ')';
        }

        $parameters['title'] = $newTitle;
    }


    public function surveyRequestTitle(&$parameters): void
    {

        $record = BackendUtility::getRecord($parameters['table'], $parameters['row']['uid']);

        /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);

        /** @var \Madj2k\Accelerator\Persistence\MarkerReducer $markerReducer */
        $this->markerReducer = $objectManager->get(MarkerReducer::class);

        /** @var \RKW\RkwOutcome\Domain\Repository\SurveyRequestRepository $surveyRequestRepository */
        $this->surveyRequestRepository = $objectManager->get(SurveyRequestRepository::class);

        /** @var \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequest */
        $surveyRequest = $this->surveyRequestRepository->findByUid($record['uid']);

        $process = $this->markerReducer->explode($surveyRequest->getProcess())['process'];

        //  @todo: Fix trouble with external table not found. Example: #1472074485: Table 'rkw_komze_dev.tx_rkwshop_domain_model_author' doesn't exist
        /** @todo: problem ist hier meist, dass die TypoScript-Definition nicht in der Rootpage eingebunden ist. Einige Extensions haben wir da gerne mal vergessen! */
        if ($process instanceof \RKW\RkwShop\Domain\Model\Order) {
            $newTitle = '[Bestellung] ' . $process->getUid() . ' - ' . $process->getFrontendUser()->getEmail();
        }

        if ($process instanceof \RKW\RkwEvents\Domain\Model\EventReservation) {
            $newTitle = '[Reservierung] ' . $process->getUid();
        }

        $parameters['title'] = $newTitle;
    }


}
