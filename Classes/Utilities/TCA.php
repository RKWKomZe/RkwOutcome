<?php
namespace RKW\RkwOutcome\Utilities;
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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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

    public function surveyConfigurationTitle(&$parameters)
    {

        $record = BackendUtility::getRecord($parameters['table'], $parameters['row']['uid']);
        $objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');

        $newTitle = '';

        //  @todo: Fix trouble with external table not found. Example: #1472074485: Table 'rkw_komze_dev.tx_rkwshop_domain_model_author' doesn't exist
        if ($record['process_type'] === '\RKW\RkwShop\Domain\Model\Product') {
//            $processRepository = $objectManager->get('RKW\RkwShop\Domain\Repository\ProductRepository');
//            $process = $processRepository->findByUid($record['product']);
//            $newTitle = '[Product] ' . $process->getTitle;
            $newTitle = '[Product] ' . $record['product'] . ' - ' . $record['target_group'];
        } else {
//            $processRepository = $objectManager->get('RKW\RkwEvents\Domain\Repository\EventRepository');
//            $process = $processRepository->findByUid($record['event']);
//            $start = BackendUtility::datetime((int)$process->getStart());
//            $newTitle = '[Event] ' . $start . ' - ' . $record['title'];
            $newTitle = '[Event] ' . $record['event'] . ' - ' . $record['target_group'];
        }

        $parameters['title'] = $newTitle;
    }

}