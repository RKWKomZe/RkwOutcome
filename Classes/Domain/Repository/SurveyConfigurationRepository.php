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

use RKW\RkwEvents\Domain\Model\Event;
use RKW\RkwShop\Domain\Model\Product;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

/**
 * Class SurveyConfigurationRepository
 *
 * @author Christian Dilger <c.dilger@addorange.de>
 * @copyright Rkw Kompetenzzentrum
 * @package RKW_RkwOutcome
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class SurveyConfigurationRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{

    /**
     * @return void
    */
    public function initializeObject(): void
    {
        $this->defaultQuerySettings = $this->objectManager->get(Typo3QuerySettings::class);
        $this->defaultQuerySettings->setRespectStoragePage(false);
    }


    /**
     * Finds survey configurations matching product and target group
     *
     * @param \RKW\RkwShop\Domain\Model\Product $product
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $targetGroups
     * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface|null
     * implicitly tested
     */
    public function findByProductAndTargetGroup(Product $product, ObjectStorage $targetGroups): ?QueryResultInterface
    {
        // 1. build uid list
        $targetGroupsList = [];

        /** @var \TYPO3\CMS\Extbase\Domain\Model\Category $category */
        foreach ($targetGroups as $targetGroup) {
            if ($targetGroup instanceof \TYPO3\CMS\Extbase\Domain\Model\Category) {
                $targetGroupsList[] = $targetGroup->getUid();
            }
        }

        if (count($targetGroupsList)) {

            $tableName = 'tx_rkwoutcome_domain_model_surveyconfiguration';
            /** @var \TYPO3\CMS\Core\Database\Query\QueryBuilder $queryBuilder */
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($tableName);

            $constraints = [
                $queryBuilder->expr()->eq(
                    'sc_mm.tablenames',
                    $queryBuilder->createNamedParameter('tx_rkwoutcome_domain_model_surveyconfiguration', \PDO::PARAM_STR)
                ),
                $queryBuilder->expr()->eq(
                    'sc_mm.fieldname',
                    $queryBuilder->createNamedParameter('target_group', \PDO::PARAM_STR)
                ),
                $queryBuilder->expr()->in(
                    'sc_mm.uid_local',
                    $queryBuilder->createNamedParameter(implode(',', $targetGroupsList), \PDO::PARAM_STR)
                ),
                $queryBuilder->expr()->eq(
                    $tableName . '.process_type',
                    $queryBuilder->createNamedParameter(\RKW\RkwShop\Domain\Model\Product::class, \PDO::PARAM_STR)
                ),
                $queryBuilder->expr()->eq(
                    $tableName . '.product',
                    $queryBuilder->createNamedParameter($product->getUid(), \PDO::PARAM_INT)
                ),
            ];

            $statement = $queryBuilder
                ->select('*')
                ->from($tableName)
                ->leftJoin(
                    $tableName,
                    'sys_category_record_mm',
                    'sc_mm',
                    $queryBuilder->expr()->eq(
                        'sc_mm.uid_foreign',
                        $queryBuilder->quoteIdentifier($tableName . '.uid')
                    )
                )
                ->where(...$constraints);

            // build final query
            $query = $this->createQuery();
            $query->statement(
                $statement
            );

            return $query->execute();

        }

        return null;
    }


    /**
     * Finds survey configurations matching event and target group
     *
     * @param \RKW\RkwEvents\Domain\Model\Event $event
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $targetGroups
     * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface|null
     * implicitly tested
     */
    public function findByEventAndTargetGroup(Event $event, ObjectStorage $targetGroups): ?QueryResultInterface
    {
        // 1. build uid list
        $targetGroupsList = [];

        /** @var \TYPO3\CMS\Extbase\Domain\Model\Category $category */
        foreach ($targetGroups as $targetGroup) {
            if ($targetGroup instanceof \TYPO3\CMS\Extbase\Domain\Model\Category) {
                $targetGroupsList[] = $targetGroup->getUid();
            }
        }

        if (count($targetGroupsList)) {

            $tableName = 'tx_rkwoutcome_domain_model_surveyconfiguration';
            /** @var \TYPO3\CMS\Core\Database\Query\QueryBuilder $queryBuilder */
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($tableName);

            $constraints = [
                $queryBuilder->expr()->eq(
                    'sc_mm.tablenames',
                    $queryBuilder->createNamedParameter('tx_rkwoutcome_domain_model_surveyconfiguration', \PDO::PARAM_STR)
                ),
                $queryBuilder->expr()->eq(
                    'sc_mm.fieldname',
                    $queryBuilder->createNamedParameter('target_group', \PDO::PARAM_STR)
                ),
                $queryBuilder->expr()->in('sc_mm.uid_local',
                    $queryBuilder->createNamedParameter(implode(',', $targetGroupsList), \PDO::PARAM_STR)
                ),
                $queryBuilder->expr()->eq(
                    $tableName . '.process_type',
                    $queryBuilder->createNamedParameter(\RKW\RkwEvents\Domain\Model\Event::class, \PDO::PARAM_STR)
                ),
                $queryBuilder->expr()->eq(
                    $tableName . '.event',
                    $queryBuilder->createNamedParameter($event->getUid(), \PDO::PARAM_INT)
                ),
            ];

            $statement = $queryBuilder
                ->select('*')
                ->from($tableName)
                ->leftJoin(
                    $tableName,
                    'sys_category_record_mm',
                    'sc_mm',
                    $queryBuilder->expr()->eq(
                        'sc_mm.uid_foreign',
                        $queryBuilder->quoteIdentifier($tableName . '.uid')
                    )
                )
                ->where(...$constraints);

            // build final query
            $query = $this->createQuery();
            $query->statement(
                $statement
            );

            return $query->execute();
        }

        return null;
    }

}
