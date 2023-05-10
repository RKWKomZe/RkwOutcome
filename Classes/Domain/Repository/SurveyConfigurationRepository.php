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

use Madj2k\CoreExtended\Utility\QueryUtility;
use RKW\RkwEvents\Domain\Model\Event;
use RKW\RkwOutcome\Domain\Model\SurveyConfiguration;
use RKW\RkwShop\Domain\Model\Product;
use TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * SurveyConfigurationRepository
 *
 * @author Christian Dilger <c.dilger@addorange.de>
 * @copyright Rkw Kompetenzzentrum
 * @package RKW_RkwOutcome
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class SurveyConfigurationRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
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
     * Finds a survey configuration matching the given product.
     *
     * @param \RKW\RkwShop\Domain\Model\Product $product
     * @return \RKW\RkwOutcome\Domain\Model\SurveyConfiguration|null
     * @todo Laut CodeInspection wird die Methode nie benutzt. Könnte also auch raus.
     */
    public function findByProduct(Product $product):? SurveyConfiguration
    {
        $query = $this->createQuery();
        $query->matching(
            $query->equals('product', $product)
        );

        $query->setLimit(1);
        return $query->execute()->getFirst();
    }


    /**
     * Finds a survey configuration matching the given identifier.
     *
     * @param \RKW\RkwShop\Domain\Model\Product $product
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $targetGroups
     * @return object|null The object for the identifier if it is known, or NULL
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     * @todo Der Rückgabewert wäre zu definieren. Siehe aber unten.
     */
    public function findByProductAndTargetGroup(Product $product, ObjectStorage $targetGroups)
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

            /** @todo Das geht theoretisch auch mit Bordmitteln: https://docs.typo3.org/m/typo3/reference-coreapi/8.7/en-us/ApiOverview/Database/QueryBuilder/Index.html#join-innerjoin-rightjoin-and-leftjoin */
            // 2. set leftJoin over categories
            $leftJoin = '
                LEFT JOIN sys_category_record_mm AS sys_category_record_mm
                    ON tx_rkwoutcome_domain_model_surveyconfiguration.uid=sys_category_record_mm.uid_foreign
                    AND sys_category_record_mm.tablenames = "tx_rkwoutcome_domain_model_surveyconfiguration"
                    AND sys_category_record_mm.fieldname = "target_group"
                LEFT JOIN sys_category AS sys_category
                    ON sys_category_record_mm.uid_local=sys_category.uid
                    AND sys_category.deleted = 0
            ';

            // 3. set constraints
            $constraints = [
                '(((sys_category.sys_language_uid IN (0,-1))) OR sys_category.uid IS NULL)',
                'tx_rkwoutcome_domain_model_surveyconfiguration.product = ' . $product->getUid(),
            ];

            /** @todo Wenn der leftJoin mit Bordmitteln gemacht ist, kann man das hier auch umbauen **/
            // 5. Final statement
            $finalStatement = '
                SELECT tx_rkwoutcome_domain_model_surveyconfiguration.*
                FROM tx_rkwoutcome_domain_model_surveyconfiguration
                ' . $leftJoin . '
                WHERE
                    sys_category.uid IN(' . implode(',', $targetGroupsList) . ')
                    AND ' . implode(' AND ', $constraints) .
                QueryUtility::getWhereClauseEnabled('tx_rkwoutcome_domain_model_surveyconfiguration') .
                QueryUtility::getWhereClauseDeleted('tx_rkwoutcome_domain_model_surveyconfiguration');

            // build final query
            $query = $this->createQuery();
            $query->getQuerySettings()->setRespectStoragePage(false);
            $query->statement(
                $finalStatement
            );

            /** @todo Hier bin ich mir unsicher: Laut Query kann mehr als ein Element zurückkommen. Möchtest du ein Objekt oder mehrere zurückgeben? */
            /** @var \RKW\RkwOutcome\Domain\Model\SurveyConfiguration $surveyConfiguration */
            return $query->execute();

        }

        return null;

    }


    /**
     * Finds a survey configuration matching the given identifier.
     *
     * @param \RKW\RkwEvents\Domain\Model\Event $event
     * @return \RKW\RkwOutcome\Domain\Model\SurveyConfiguration|null
     * @todo Laut CodeInspection wird die Methode nie benutzt. Könnte also auch raus.
     */
    public function findByEvent(Event $event):? SurveyConfiguration
    {
        $query = $this->createQuery();

        $query->matching(
            $query->equals('event', $event)
        );

        $query->setLimit(1);
        return $query->execute()->getFirst();
    }


    /**
     * Finds a survey configuration matching the given identifier.
     *
     * @param \RKW\RkwEvents\Domain\Model\Event $event
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $targetGroups
     * @return object|null
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     * @todo Der Rückgabewert wäre zu definieren. Siehe aber unten.
     */
    public function findByEventAndTargetGroup(Event $event, ObjectStorage $targetGroups)
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
            /** @todo Das geht theoretisch auch mit Bordmitteln: https://docs.typo3.org/m/typo3/reference-coreapi/8.7/en-us/ApiOverview/Database/QueryBuilder/Index.html#join-innerjoin-rightjoin-and-leftjoin */
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
            /** @todo Wenn der leftJoin mit Bordmitteln gemacht ist, kann man das hier auch umbauen **/
            $finalStatement = '
                SELECT tx_rkwoutcome_domain_model_surveyconfiguration.*
                FROM tx_rkwoutcome_domain_model_surveyconfiguration
                ' . $leftJoin . '
                WHERE
                    sys_category.uid IN(' . implode(',', $targetGroupsList) . ')
                    AND ' . implode(' AND ', $constraints) .
                QueryUtility::getWhereClauseEnabled('tx_rkwoutcome_domain_model_surveyconfiguration') .
                QueryUtility::getWhereClauseDeleted('tx_rkwoutcome_domain_model_surveyconfiguration');

            // build final query
            $query = $this->createQuery();
            $query->getQuerySettings()->setRespectStoragePage(false);
            $query->statement(
                $finalStatement
            );

            /** @todo Hier bin ich mir unsicher: Laut Query kann mehr als ein Element zurückkommen. Möchtest du ein Objekt oder mehrere zurückgeben? */
            /** @var \RKW\RkwOutcome\Domain\Model\SurveyConfiguration $surveyConfiguration */
            return $query->execute();

        }

        return null;

    }

}
