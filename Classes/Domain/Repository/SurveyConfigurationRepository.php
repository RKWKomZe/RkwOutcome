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

use RKW\RkwBasics\Helper\QueryTypo3;
use RKW\RkwEvents\Domain\Model\Event;
use RKW\RkwOutcome\Domain\Model\SurveyConfiguration;
use RKW\RkwShop\Domain\Model\Product;
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

    /*
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
     *
     * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface|null
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
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

            // 2. set leftJoin over categories
            $leftJoin = '
                LEFT JOIN sys_category_record_mm AS sys_category_record_mm
                    ON tx_rkwoutcome_domain_model_surveyconfiguration.uid=sys_category_record_mm.uid_foreign
                    AND sys_category_record_mm.tablenames = \'tx_rkwoutcome_domain_model_surveyconfiguration\'
                    AND sys_category_record_mm.fieldname = \'target_group\'
                LEFT JOIN sys_category AS sys_category
                    ON sys_category_record_mm.uid_local=sys_category.uid
                    AND sys_category.deleted = 0
            ';

            // 3. set constraints
            $constraints = [
                '(((sys_category.sys_language_uid IN (0,-1))) OR sys_category.uid IS NULL)',
                'tx_rkwoutcome_domain_model_surveyconfiguration.product = ' . $product->getUid(),
            ];

            // 5. Final statement
            $finalStatement = '
                SELECT tx_rkwoutcome_domain_model_surveyconfiguration.*
                FROM tx_rkwoutcome_domain_model_surveyconfiguration
                ' . $leftJoin . '
                WHERE
                    sys_category.uid IN(' . implode(',', $targetGroupsList) . ')
                    AND ' . implode(' AND ', $constraints) .
                QueryTypo3::getWhereClauseForEnableFields('tx_rkwoutcome_domain_model_surveyconfiguration') .
                QueryTypo3::getWhereClauseForDeleteFields('tx_rkwoutcome_domain_model_surveyconfiguration')
                ;

            // build final query
            $query = $this->createQuery();
            $query->statement(
                $finalStatement
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
     *
     * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface|null
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
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

            // 2. set leftJoin over categories
            $leftJoin = '
                LEFT JOIN sys_category_record_mm AS sys_category_record_mm
                    ON tx_rkwoutcome_domain_model_surveyconfiguration.uid=sys_category_record_mm.uid_foreign
                    AND sys_category_record_mm.tablenames = \'tx_rkwoutcome_domain_model_surveyconfiguration\'
                    AND sys_category_record_mm.fieldname = \'target_group\'
                LEFT JOIN sys_category AS sys_category
                    ON sys_category_record_mm.uid_local=sys_category.uid
                    AND sys_category.deleted = 0
            ';

            // 3. set constraints
            $constraints = [
                '(((sys_category.sys_language_uid IN (0,-1))) OR sys_category.uid IS NULL)',
                'tx_rkwoutcome_domain_model_surveyconfiguration.event = ' . $event->getUid(),
            ];

            // 5. Final statement
            $finalStatement = '
                SELECT tx_rkwoutcome_domain_model_surveyconfiguration.*
                FROM tx_rkwoutcome_domain_model_surveyconfiguration
                ' . $leftJoin . '
                WHERE
                    sys_category.uid IN(' . implode(',', $targetGroupsList) . ')
                    AND ' . implode(' AND ', $constraints) .
                QueryTypo3::getWhereClauseForEnableFields('tx_rkwoutcome_domain_model_surveyconfiguration') .
                QueryTypo3::getWhereClauseForDeleteFields('tx_rkwoutcome_domain_model_surveyconfiguration')
            ;

            // build final query
            $query = $this->createQuery();
            $query->statement(
                $finalStatement
            );

            return $query->execute();

        }

        return null;
    }

}
