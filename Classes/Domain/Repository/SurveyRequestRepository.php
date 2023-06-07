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

use RKW\RkwMailer\Persistence\MarkerReducer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
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
    /*
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
     * @param int $currentTime
     * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface|null
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     * implicitly tested
     */
    public function findPendingSurveyRequests(int $currentTime = 0): ?QueryResultInterface
    {
        if (! $currentTime) {
            $currentTime = time();
        }

        $query = $this->createQuery();

        $constraints[] =
            $query->logicalAnd(
                $query->equals('notifiedTstamp', 0),
                $query->equals('deleted', 0)
            )
        ;

        $query->matching($query->logicalAnd($constraints));

        return $query->execute();
    }


    /**
     * Finds all pending survey requests due to be processed
     * and groups them by attached frontend user
     *
     * @param int $currentTime
     * @return array
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     * implicitly tested
     */
    public function findPendingSurveyRequestsGroupedByFrontendUser(int $currentTime): array
    {
        /** @var  \TYPO3\CMS\Extbase\Persistence\QueryResultInterface $surveyRequests */
        $surveyRequests = $this->findPendingSurveyRequests($currentTime);

        $surveyRequestsGroupedByFrontendUser = [];

        /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);

        /** @var \RKW\RkwMailer\Persistence\MarkerReducer $markerReducer */
        $markerReducer = $objectManager->get(MarkerReducer::class);

        /** @var \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequest */
        foreach ($surveyRequests as $surveyRequest) {

            $process = $markerReducer->explodeMarker($surveyRequest->getProcess())['process'];
            /** @var \RKW\RkwOutcome\Domain\Model\SurveyConfiguration $surveyConfiguration */
            $surveyConfiguration = $surveyRequest->getSurveyConfiguration();
            $surveyWaitingTime = $surveyConfiguration->getSurveyWaitingTime();

            if (
                (
                    $process instanceof \RKW\RkwShop\Domain\Model\Order
                    && $process->getShippedTstamp() < ($currentTime - $surveyWaitingTime)
                )
                ||
                (
                    $process instanceof \RKW\RkwEvents\Domain\Model\EventReservation
                    && $process->getEvent()->getEnd() < ($currentTime - $surveyWaitingTime)
                )
            ) {
                $surveyRequestsGroupedByFrontendUser[$surveyRequest->getFrontendUser()->getUid()][] = $surveyRequest;
            }

        }

        return $surveyRequestsGroupedByFrontendUser;
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

        $constraints[] =
            $query->logicalAnd(
                $query->greaterThan('notifiedTstamp', 0),
                $query->equals('frontend_user', $frontendUserUid)
            )
        ;

        $query->matching($query->logicalAnd($constraints));

        return $query->execute();
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

        $constraints[] =
            $query->logicalAnd(
                $query->greaterThan('notifiedTstamp', $currentTime - $period),
                $query->equals('frontend_user', $frontendUserUid)
            )
        ;

        $query->matching($query->logicalAnd($constraints));

        return $query->execute();

    }

}
