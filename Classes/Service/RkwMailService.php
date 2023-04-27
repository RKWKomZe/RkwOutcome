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

use RKW\RkwBasics\Utility\GeneralUtility;
use RKW\RkwMailer\Service\MailService;
use RKW\RkwMailer\Utility\FrontendLocalizationUtility;
use RKW\RkwOutcome\Manager\SurveyRequestManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class RkwMailService
 *
 * @author Christian Dilger <c.dilger@addorange.de>
 * @copyright Rkw Kompetenzzentrum
 * @package RKW_RkwOutcome
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class RkwMailService implements \TYPO3\CMS\Core\SingletonInterface
{

    use LogTrait;

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
        \RKW\RkwRegistration\Domain\Model\FrontendUser $recipient,
        \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequest
    ): void {

        $this->logInfo(
            sprintf(
                'Mailer: Sending survey request %s to frontend user with %s.',
                $surveyRequest->getUid(),
                $recipient->getUid() . '(' . $recipient->getEmail() . ')'
            )
        );

        // get settings
        $settings = $this->getSettings(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
        $settingsDefault = $this->getSettings();

        $this->logInfo(
            sprintf(
                'Mailer: Settings %s.',
                json_encode($settings['view'])
            )
        );

        if ($settings['view']['templateRootPaths']) {

            $this->logInfo(
                sprintf(
                    'Mailer: Build mail for survey request %s to frontend user with %s.',
                    $surveyRequest->getUid(),
                    $recipient->getUid() . '(' . $recipient->getEmail() . ')'
                )
            );

            /** @var \RKW\RkwMailer\Service\MailService $mailService */
            $mailService = GeneralUtility::makeInstance(MailService::class);

            if (
                ($recipient instanceof \RKW\RkwRegistration\Domain\Model\FrontendUser)
                && ($recipient->getEmail())
            ) {

                /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
                $objectManager = GeneralUtility::makeInstance(ObjectManager::class);

                /** @var \RKW\RkwOutcome\Manager\SurveyRequestManager $surveyRequestManager */
                $surveyRequestManager = $objectManager->get(SurveyRequestManager::class);

                $mailService->setTo($recipient, [
                    'marker'  => [
                        'surveyRequest' => $surveyRequest,
                        'frontendUser' => $recipient,
                        'surveyRequestTags' => $surveyRequestManager->buildSurveyRequestTags($surveyRequest),
                        'surveyPid' => (int) $settingsDefault['surveyShowPid']
                    ]
                ]);

                $mailService->getQueueMail()->setSubject(
                    FrontendLocalizationUtility::translate(
                        'rkwMailService.subject.userSurveyRequestNotification',
                        'rkw_outcome',
                        null,
                        $recipient->getTxRkwregistrationLanguageKey()
                    )
                );

                $this->logInfo(
                    sprintf(
                        'Mailer: Prepared mail for survey request %s to frontend user with %s.',
                        $surveyRequest->getUid(),
                        $recipient->getUid() . '(' . $recipient->getEmail() . ')'
                    )
                );
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
        return GeneralUtility::getTyposcriptConfiguration('Rkwoutcome', $which);
    }

}
