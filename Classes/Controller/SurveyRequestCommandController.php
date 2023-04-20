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

use RKW\RkwBasics\Utility\GeneralUtility;
use RKW\RkwOutcome\Manager\SurveyRequestManager;
use RKW\RkwOutcome\Service\LogTrait;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class SurveyRequestCommandController
 *
 * @author Christian Dilger <c.dilger@addorange.de>
 * @copyright Rkw Kompetenzzentrum
 * @package RKW_RkwOutcome
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class SurveyRequestCommandController extends \TYPO3\CMS\Extbase\Mvc\Controller\CommandController
{

    use LogTrait;

    /**
     * @var \RKW\RkwOutcome\Domain\Repository\SurveyRequestRepository
     * @inject
     */
    protected $surveyRequestRepository;


    /**
     * process all pending survey requests
     *
     * @param int $checkPeriod
     * @param int $maxSurveysPerPeriodAndFrontendUser
     * @param int $surveyWaitingTime
     * @return void
     */
    public function processSurveyRequestsCommand(int $checkPeriod, int $maxSurveysPerPeriodAndFrontendUser, int $surveyWaitingTime = 0): void
    {

        try {

            /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
            $objectManager = GeneralUtility::makeInstance(ObjectManager::class);

            /** @var \RKW\RkwOutcome\Manager\SurveyRequestManager $surveyRequestManager */
            $surveyRequestManager = $objectManager->get(SurveyRequestManager::class);
            $surveyRequestManager->processPendingSurveyRequests($checkPeriod, $maxSurveysPerPeriodAndFrontendUser, $surveyWaitingTime);

        } catch (\Exception $e) {

            $this->logError(
                sprintf(
                    'An unexpected error occurred while trying to process survey requests: %s',
                    $e->getMessage()
                )
            );

        }

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
        return GeneralUtility::getTyposcriptConfiguration('Rkwoutcome', $which);
    }

}
