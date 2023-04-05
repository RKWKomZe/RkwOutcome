<?php

namespace RKW\RkwOutcome\Domain\Repository;
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

use TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

/**
 * SurveyRequestRepository
 *
 * @author Christian Dilger <c.dilger@addorange.de>
 * @copyright Rkw Kompetenzzentrum
 * @package RKW_RkwOutcome
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class SurveyRequestRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{

    /*
    * initializeObject
    */
    public function initializeObject()
    {
        $this->defaultQuerySettings = $this->objectManager->get(Typo3QuerySettings::class);
        $this->defaultQuerySettings->setRespectStoragePage(false);
    }


    /**
     * findAllPendingSurveyRequestsOlderThanNowMinusSurveyWaitingTime
     *
     * @param int $surveyWaitingTime
     * @param int $currentTime
     * @return QueryResultInterface
     * @throws InvalidQueryException
     * @comment implicitly tested
     */
    public function findAllPendingSurveyRequests(int $surveyWaitingTime = 0, int $currentTime = 0): \TYPO3\CMS\Extbase\Persistence\QueryResultInterface
    {

        if (! $currentTime) {
            $currentTime = time();
        }

        $query = $this->createQuery();

        $constraints = [];

        $constraints[] =
            $query->logicalOr(
                $query->logicalAnd(
                    $query->equals('notifiedTstamp', 0),
                    $query->lessThan('order.shippedTstamp', $currentTime - $surveyWaitingTime),
                    $query->greaterThan('order', 0)
                ),
                $query->logicalAnd(
                    $query->equals('notifiedTstamp', 0),
                    $query->lessThan('eventReservation.event.end', $currentTime - $surveyWaitingTime),
                    $query->greaterThan('eventReservation', 0)
                )
            )
        ;

        // NOW: construct final query!
        if ($constraints) {
            $query->matching($query->logicalAnd($constraints));
        }

        return $query->execute();

    }


    /**
     * findAllPendingSurveyRequestsGroupedByFrontendUser
     *
     * @param int $surveyWaitingTime
     * @param int $currentTime
     * @return array
     * @throws InvalidQueryException
     * @comment implicitly tested
     */
    public function findAllPendingSurveyRequestsGroupedByFrontendUser(int $surveyWaitingTime, int $currentTime): array
    {

        /** @var  \TYPO3\CMS\Extbase\Persistence\QueryResultInterface $surveyRequests */
        $surveyRequests = $this->findAllPendingSurveyRequests($surveyWaitingTime, $currentTime);

        $surveyRequestsGroupedByFrontendUser = [];

        foreach ($surveyRequests as $surveyRequest) {

            $surveyRequestsGroupedByFrontendUser[$surveyRequest->getFrontendUser()->getUid()][] = $surveyRequest;

        }

        return $surveyRequestsGroupedByFrontendUser;

    }


    /**
     * findAllPendingSurveyRequestsGroupedByFrontendUser
     *
     * @param int $frontendUserUid
     * @param int $period
     * @param int $currentTime
     * @return QueryResultInterface
     * @throws InvalidQueryException
     * @comment implicitly tested
     */
    public function findAllNotifiedSurveyRequestsWithinPeriodByFrontendUser(int $frontendUserUid, int $period, int $currentTime = 0): \TYPO3\CMS\Extbase\Persistence\QueryResultInterface
    {

        if (! $currentTime) {
            $currentTime = time();
        }

        $query = $this->createQuery();

        $constraints = [];

        $constraints[] =
            $query->logicalAnd(
                $query->greaterThan('notifiedTstamp', $currentTime - $period),
                $query->equals('frontend_user', $frontendUserUid)
            )
        ;

        // NOW: construct final query!
        if ($constraints) {
            $query->matching($query->logicalAnd($constraints));
        }

        return $query->execute();

    }

}
