<?php

namespace RKW\RkwOutcome\Service;

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

use RKW\RkwMailer\Service\MailService;
use RKW\RkwMailer\Utility\FrontendLocalizationUtility;
use RKW\RkwOutcome\Domain\Model\SurveyRequest;
use RKW\RkwRegistration\Domain\Model\FrontendUser;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;

/**
 * RkwOutcome
 *
 * @author Christian Dilger <c.dilger@addorange.de>
 * @copyright Rkw Kompetenzzentrum
 * @package RKW_RkwOutcome
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class RkwMailService implements \TYPO3\CMS\Core\SingletonInterface
{

    /**
     * Send mail to frontend user to submit survey request
     *
     * @param \RKW\RkwRegistration\Domain\Model\FrontendUser $recipient
     * @param \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequest
     * @return void
     * @throws \Exception
     * @throws \RKW\RkwMailer\Exception
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     * @throws \TYPO3Fluid\Fluid\View\Exception\InvalidTemplateResourceException
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     */
    public function sendMailSurveyRequestToUser(
        FrontendUser $recipient,
        SurveyRequest $surveyRequest,
    ): void {

        // get settings
        $settings = $this->getSettings(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
        if ($settings['view']['templateRootPaths']) {

            /** @var \RKW\RkwMailer\Service\MailService $mailService */
            $mailService = GeneralUtility::makeInstance(MailService::class);

            if ($recipient->getEmail()) {

                // send new user an email with token
                $mailService->setTo($recipient, [
                    'marker'  => array(
                        'surveyRequest'    => $surveyRequest,
                        'frontendUser' => $recipient,
                    ),
                    'subject' => FrontendLocalizationUtility::translate(
                        'rkwMailService.subject.userSurveyRequestNotification',
                        'rkw_outcome',
                        null,
                        'de'
                    ),
                ]);

            }

            $mailService->getQueueMail()->addTemplatePaths($settings['view']['templateRootPaths']);
            $mailService->getQueueMail()->addPartialPaths($settings['view']['partialRootPaths']);

            $mailService->getQueueMail()->setPlaintextTemplate('Email/UserSurveyRequest');
            $mailService->getQueueMail()->setHtmlTemplate('Email/UserSurveyRequest');

            $mailService->send();
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
        return \RKW\RkwBasics\Utility\GeneralUtility::getTyposcriptConfiguration('Rkwoutcome', $which);
    }


}
