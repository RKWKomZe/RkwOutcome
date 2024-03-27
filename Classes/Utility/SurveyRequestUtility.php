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
use RKW\RkwOutcome\Domain\Model\SurveyRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class SurveyRequestUtility
 *
 * @author Christian Dilger <c.dilger@addorange.de>
 * @copyright Rkw Kompetenzzentrum
 * @package RKW_RkwOutcome
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class SurveyRequestUtility
{

    /**
     * @param \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequest
     * @return string
     */
    public static function buildSurveyRequestTags(SurveyRequest $surveyRequest): string
    {
        $surveyRequest->getTargetGroup()->rewind();
        $targetGroupUid = $surveyRequest->getTargetGroup()->current()->getUid();

        $objectDefinition = GeneralUtility::trimExplode(':', $surveyRequest->getProcessSubject()['processSubject']);
        $processSubjectFQDNAsArray = GeneralUtility::trimExplode('\\', $objectDefinition[0]);
        $processSubjectModelName = array_pop($processSubjectFQDNAsArray);
        $processSubjectUid = (int)$objectDefinition[1];

        $surveyRequestTags = [
            $targetGroupUid,
            $processSubjectModelName,
            $processSubjectUid
        ];

        return implode(',', $surveyRequestTags);
    }


    /**
     * @param \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequest
     * @return array
     * @throws \TYPO3\CMS\Extbase\Object\Exception
     */
    public static function getProcessInformation(SurveyRequest $surveyRequest): array
    {
        /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
        $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ObjectManager::class);

        /** @var \Madj2k\Accelerator\Persistence\MarkerReducer $markerReducer */
        $markerReducer = $objectManager->get(MarkerReducer::class);

        $surveyRequest->getTargetGroup()->rewind();
        $targetGroupUid = $surveyRequest->getTargetGroup()->current()->getUid();

        $processMarker = $markerReducer->explode($surveyRequest->getProcess());
        $process = $processMarker['process'];

        $processSubjectMarker = $markerReducer->explode($surveyRequest->getProcessSubject());
        $processSubject = $processSubjectMarker['processSubject'];

        return [
            'date' => $process->getCrdate(),
            'subject' => [
                'name' => $processSubject->getTitle()
            ],
            'targetGroup' => [
                'name' => $surveyRequest->getTargetGroup()->current()->getTitle()
            ]
        ];
    }

}
