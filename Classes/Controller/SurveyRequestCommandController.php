<?php

namespace RKW\RkwOutcome\Controller;
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

use RKW\RkwOutcome\Manager\SurveyRequestManager;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * SurveyRequestCommandController
 *
 * @author Christian Dilger <c.dilger@addorange.de>
 * @copyright Rkw Kompetenzzentrum
 * @package RKW_RkwOutcome
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class SurveyRequestCommandController extends \TYPO3\CMS\Extbase\Mvc\Controller\CommandController
{

    /**
     * surveyRequestsRepository
     *
     * @var \RKW\RkwOutcome\Domain\Repository\SurveyRequestRepository
     * @inject
     */
    protected $surveyRequestRepository;


    /**
     * @var \TYPO3\CMS\Core\Log\Logger
     */
    protected $logger;


    /**
     * process all pending survey requests
     *
     * @param int $tolerance Tolerance for creating next issue according to last time an issue was built (in seconds)
     * @return void
     */
    public function processSurveyRequestsCommand(int $tolerance = 0): void
    {

        /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);

        /** @var \RKW\RkwOutcome\Manager\SurveyRequestManager $surveyRequestManager */
        $surveyRequestManager = $objectManager->get(SurveyRequestManager::class);
        $surveyRequestManager->processPendingSurveyRequests($tolerance);


//        try {
//
//            /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
//            $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
//
//            /** @var \RKW\RkwOutcome\Manager\SurveyRequestManager $surveyRequestManager */
//            $surveyRequestManager = $objectManager->get(SurveyRequestManager::class);
//            $surveyRequestManager->processPendingSurveyRequests($tolerance);
//
//        } catch (\Exception $e) {
//
//            $this->getLogger()->log(
//                LogLevel::ERROR,
//                sprintf(
//                    'An unexpected error occurred while trying to process survey requests: %s',
//                    $e->getMessage()
//                )
//            );
//
//        }
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


    /**
     * Returns TYPO3 settings
     *
     * @param string $which Which type of settings will be loaded
     * @return array
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     */
    protected function getSettings(string $which = ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS): array
    {
        return \RKW\RkwBasics\Utility\GeneralUtility::getTyposcriptConfiguration('Rkwoutcome', $which);
    }

}