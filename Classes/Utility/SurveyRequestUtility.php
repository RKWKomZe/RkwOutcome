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

use RKW\RkwOutcome\Domain\Model\SurveyRequest;

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
     * @var \RKW\RkwMailer\Persistence\MarkerReducer|null
     */
    protected $markerReducer;


    /**
     * @param \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequest
     * @return string
     */
    public static function buildSurveyRequestTags(SurveyRequest $surveyRequest): string
    {
        $surveyRequest->getTargetGroup()->rewind();
        $targetGroupUid = $surveyRequest->getTargetGroup()->current()->getUid();

        $processSubject = explode(':', $surveyRequest->getProcessSubject()['processSubject']);
        $processSubject[0] = explode('\\', $processSubject[0]);
        $processSubjectType = array_pop($processSubject[0]);
        $processSubjectUid = $processSubject[1];

        $surveyRequestTags = [
            $targetGroupUid, // targetGroupUid
            $processSubjectType, // processSubjectType
            $processSubjectUid  // processSubjectUid
        ];

        return implode(',', $surveyRequestTags);
    }
}
