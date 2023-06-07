<?php

namespace RKW\RkwOutcome\ViewHelpers\Email\Replace;

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

use Madj2k\Postmaster\Domain\Model\QueueMail;
use Madj2k\Postmaster\Domain\Model\QueueRecipient;
use Madj2k\Postmaster\UriBuilder\EmailUriBuilder;
use RKW\RkwOutcome\Domain\Model\SurveyRequest;
use RKW\RkwOutcome\Utility\SurveyRequestUtility;
use RKW\RkwSurvey\Domain\Model\Survey;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithContentArgumentAndRenderStatic;

/**
 * Class SurveyPlaceHoldersViewHelper
 *
 * @author Christian Dilger <c.dilger@addorange.de>
 * @copyright Rkw Kompetenzzentrum
 * @package RKW_RkwOutcome
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class SurveyPlaceHoldersViewHelper extends \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper
{

    use CompileWithContentArgumentAndRenderStatic;

    /**
     * @var bool
     */
    protected $escapeOutput = false;


    /**
     * Initialize arguments.
     *
     * @throws \TYPO3Fluid\Fluid\Core\ViewHelper\Exception
     */
    public function initializeArguments()
    {
        $this->registerArgument('value', 'string', 'String to work on');
        $this->registerArgument('queueMail', QueueMail::class, 'QueueMail-object for redirecting links');
        $this->registerArgument('queueRecipient', QueueRecipient::class, 'QueueRecipient-object of email');
        $this->registerArgument('isPlaintext', 'boolean', 'QueueRecipient-object of email');
        $this->registerArgument('targetUid', 'int', 'Uid of target page');
        $this->registerArgument('additionalParams', 'array', 'Additional params for links');
        $this->registerArgument('generatedTokens', 'array', 'Generated security tokens to access survey');
        $this->registerArgument('surveyRequest', SurveyRequest::class, 'SurveyRequest-object of email');

    }


    /**
     * Render typolinks
     **
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param \TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface $renderingContext
     * @return string
     */
    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ): string {

        $value = $renderChildrenClosure();
        $queueMail = $arguments['queueMail'];
        $queueRecipient = $arguments['queueRecipient'];
        $isPlaintext  = (bool) $arguments['isPlaintext'];
        $surveyRequest = $arguments['surveyRequest'];
        $targetUid = $arguments['targetUid'];
        $generatedTokens = $arguments['generatedTokens'];
        $additionalParams = $arguments['additionalParams'] ? $arguments['additionalParams'] : [] ;

        try {

            if ($queueMail) {

                // plaintext replacement
                if ($isPlaintext) {

                    foreach ($surveyRequest->getSurveyConfiguration()->getSurvey() as $survey) {

                        $actionLink = self::replace(
                            $survey,
                            $surveyRequest,
                            $targetUid,
                            $queueMail,
                            $queueRecipient,
                            $generatedTokens,
                            $additionalParams
                        );
                        $actionLinkPlain = $survey->getName() . '(' . $actionLink . ')';
                        $pattern = '/###survey' . $survey->getUid() . '###/';

                        $value = preg_replace(
                            $pattern,
                            $actionLinkPlain,
                            $value
                        );
                    }

                // HTML- replacement
                } else {

                    foreach ($surveyRequest->getSurveyConfiguration()->getSurvey() as $survey) {

                        $actionLink = self::replace(
                            $survey,
                            $surveyRequest,
                            $targetUid,
                            $queueMail,
                            $queueRecipient,
                            $generatedTokens,   //  @todo: evtl. in additional params unterbringen, ebenso wie targetUid
                            $additionalParams
                        );
                        $actionLinkHtml = '<a href="' . $actionLink . '" target="_blank">' . $survey->getName() . '</a>';
                        $pattern = '/###survey' . $survey->getUid() . '###/';

                        $value = preg_replace(
                            $pattern,
                            $actionLinkHtml,
                            $value
                        );

                    }

                }
            }

        } catch (\Exception $e) {

            $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
            $logger->log(
                LogLevel::ERROR,
                sprintf(
                    'Error while trying to replace survey links: %s',
                    $e->getMessage()
                )
            );
        }

        return $value;
    }


    /**
     * Replaces the link
     *
     * @param Survey $survey
     * @param SurveyRequest $surveyRequest
     * @param int $targetUid
     * @param QueueMail $queueMail
     * @param QueueRecipient|null $queueRecipient
     * @param array $generatedTokens
     * @param array $additionalParams
     * @return string
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     */
    static protected function replace(
        Survey $survey,
        SurveyRequest $surveyRequest,
        int $targetUid,
        QueueMail $queueMail,
        QueueRecipient $queueRecipient = null,
        array $generatedTokens = [],
        array $additionalParams = []
    ): string {

        // load EmailUriBuilder
        /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);

        /** @var \Madj2k\Postmaster\UriBuilder\EmailUriBuilder $uriBuilder */
        $uriBuilder = $objectManager->get(EmailUriBuilder::class);

        $arguments = [
            'survey' => $survey,
            'tags'   => SurveyRequestUtility::buildSurveyRequestTags($surveyRequest)
        ];

        if (isset($generatedTokens[$survey->getUid()])) {
            $arguments['token'] = $generatedTokens[$survey->getUid()];
        }

        $uriBuilder
            ->reset()
            ->setQueueMail($queueMail)
            ->setTargetPageUid($targetUid)
            ->setArguments($arguments);

        if ($queueRecipient) {
            $uriBuilder->setQueueRecipient($queueRecipient);
        }

        return $uriBuilder->uriFor(
            'welcome',
            $arguments,
            'Survey',
            'RkwSurvey',
            'Survey'
        );

    }
}
