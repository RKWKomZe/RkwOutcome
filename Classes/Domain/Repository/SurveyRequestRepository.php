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
     * findAllPendingSurveyRequestsWithinTolerance
     *
     * @param int $tolerance
     *
     * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     * @comment implicitly tested
     */
    public function findAllPendingSurveyRequests(int $tolerance): \TYPO3\CMS\Extbase\Persistence\QueryResultInterface
    {

        $query = $this->createQuery();

        $constraints = [];

        //  @todo: Check on eventReservation.event.endTime, too!? - see former method isNotifiable
        $constraints[] =
            $query->logicalAnd(
                $query->equals('notifiedTstamp', 0),
                $query->lessThan('process.shippedTstamp', time() - $tolerance),
                $query->greaterThan('process', 0)
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
     * @param int $tolerance
     *
     * @return array
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     * @comment implicitly tested
     */
    public function findAllPendingSurveyRequestsGroupedByFrontendUser(int $tolerance): array
    {

        /** @var  \TYPO3\CMS\Extbase\Persistence\QueryResultInterface $surveyRequests */
        $surveyRequests = $this->findAllPendingSurveyRequests($tolerance);

        $surveyRequestsGroupedByFrontendUser = [];

        foreach ($surveyRequests as $surveyRequest) {

            $surveyRequestsGroupedByFrontendUser[$surveyRequest->getFrontendUser()->getUid()][] = $surveyRequest;

        }

        return $surveyRequestsGroupedByFrontendUser;

    }

}