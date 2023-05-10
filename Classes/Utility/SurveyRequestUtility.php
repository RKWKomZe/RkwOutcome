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

use RKW\RkwMailer\Persistence\MarkerReducer;
use RKW\RkwOutcome\Domain\Model\SurveyRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class SurveyRequestProcessor
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

        /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);

        /** @var \RKW\RkwMailer\Persistence\MarkerReducer $markerReducer */
        $markerReducer = $objectManager->get(MarkerReducer::class);

        $process = $markerReducer->explodeMarker($surveyRequest->getProcess())['process'];

        if ($process instanceof \RKW\RkwShop\Domain\Model\Order) {
            $processSubject = $surveyRequest->getOrderSubject();
        }

        if ($process instanceof \RKW\RkwEvents\Domain\Model\EventReservation) {
            $processSubject = $surveyRequest->getEventReservationSubject();
        }

        $processSubject = explode(':', $processSubject);
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
