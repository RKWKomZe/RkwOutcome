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

use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

/**
 * Class SurveyRequestRepository
 *
 * @author Christian Dilger <c.dilger@addorange.de>
 * @copyright Rkw Kompetenzzentrum
 * @package RKW_RkwOutcome
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class SurveyRequestRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{

    /**
     * initializeObject
     *
     * @return void
     */
    public function initializeObject(): void
    {
        $this->defaultQuerySettings = $this->objectManager->get(Typo3QuerySettings::class);
        $this->defaultQuerySettings->setRespectStoragePage(false);
    }


    /**
     * Finds all pending survey requests due to be processed
     *
     * @param int $surveyWaitingTime
     * @param int $currentTime
     * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface|null
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     * implicitly tested
     */
    public function findPendingSurveyRequests(int $surveyWaitingTime = 0, int $currentTime = 0): ?QueryResultInterface
    {
        if (! $currentTime) {
            $currentTime = time();
        }

        $query = $this->createQuery();

        $constraints = [];
        $constraints[] =
            $query->logicalAnd(
                $query->equals('notifiedTstamp', 0),
                $query->equals('deleted', 0),
                $query->logicalOr(
                    $query->logicalAnd(
                        $query->lessThan('order.shippedTstamp', $currentTime - $surveyWaitingTime),
                        $query->greaterThan('order', 0)
                    ),
                    $query->logicalAnd(
                        $query->lessThan('eventReservation.event.end', $currentTime - $surveyWaitingTime),
                        $query->greaterThan('eventReservation', 0)
                    )
                )
            )
        ;

        // NOW: construct final query!
        /** @todo constraints kann - rein logisch - nie leer sein. Auf Variable ganz verzichten? */
        if ($constraints) {
            $query->matching($query->logicalAnd($constraints));
        }

        return $query->execute();

    }


    /**
     * Finds all pending survey requests due to be processed
     * and groups them by attached frontend user
     *
     * @param int $surveyWaitingTime
     * @param int $currentTime
     * @return array
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     * implicitly tested
     */
    public function findPendingSurveyRequestsGroupedByFrontendUser(int $surveyWaitingTime, int $currentTime): array
    {
        /** @var  \TYPO3\CMS\Extbase\Persistence\QueryResultInterface $surveyRequests */
        $surveyRequests = $this->findPendingSurveyRequests($surveyWaitingTime, $currentTime);

        $surveyRequestsGroupedByFrontendUser = [];
        foreach ($surveyRequests as $surveyRequest) {
            $surveyRequestsGroupedByFrontendUser[$surveyRequest->getFrontendUser()->getUid()][] = $surveyRequest;
        }

        return $surveyRequestsGroupedByFrontendUser;
    }


    /**
     * Finds all notified survey requests within a given period
     * matching a given frontend user.
     *
     * @param int $frontendUserUid
     * @param int $period
     * @param int $currentTime
     * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     * implicitly tested
     */
    public function findNotifiedSurveyRequestsWithinPeriodByFrontendUser(int $frontendUserUid, int $period, int $currentTime = 0): QueryResultInterface
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
        /** @todo constraints kann - rein logisch - nie leer sein. Auf Variable ganz verzichten? */
        if ($constraints) {
            $query->matching($query->logicalAnd($constraints));
        }

        return $query->execute();
    }


    /**
     * Finds all notified survey requests
     * matching a given frontend user
     *
     * @param int $frontendUserUid
     * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     * implicitly tested
     */
    public function findNotifiedSurveyRequestsByFrontendUser(int $frontendUserUid): QueryResultInterface
    {
        $query = $this->createQuery();

        $constraints = [];
        $constraints[] =
            $query->logicalAnd(
                $query->greaterThan('notifiedTstamp', 0),
                $query->equals('frontend_user', $frontendUserUid)
            )
        ;

        // NOW: construct final query!
        /** @todo constraints kann - rein logisch - nie leer sein. Auf Variable ganz verzichten? */
        if ($constraints) {
            $query->matching($query->logicalAnd($constraints));
        }

        return $query->execute();
    }

}
