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
use TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;

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

    /*
    * initializeObject
    */
    public function initializeObject()
    {
        $this->defaultQuerySettings = $this->objectManager->get(Typo3QuerySettings::class);
        $this->defaultQuerySettings->setRespectStoragePage(false);
    }

    /**
     * Finds a survey configuration matching the given identifier.
     *
     * @param \RKW\RkwShop\Domain\Model\Product $product
     *
     * @return object|null The object for the identifier if it is known, or NULL
     */
    public function findByProduct(\RKW\RkwShop\Domain\Model\Product $product)
    {
        $query = $this->createQuery();

        $query->matching(
            $query->equals('product', $product)
        );

        $query->setLimit(1);

        /** @var \RKW\RkwOutcome\Domain\Model\SurveyConfiguration $surveyConfiguration */
        return $query->execute()->getFirst();
    }


    /**
     * Finds a survey configuration matching the given identifier.
     *
     * @param \RKW\RkwShop\Domain\Model\Product            $product
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $targetCategpries
     *
     * @return object|null The object for the identifier if it is known, or NULL
     * @throws InvalidQueryException
     */
    public function findByProductAndTargetGroup(\RKW\RkwShop\Domain\Model\Product $product, \TYPO3\CMS\Extbase\Persistence\ObjectStorage $targetCategories)
    {

        // 1. build uid list
        $sysCategoriesList = [];

        /** @var \TYPO3\CMS\Extbase\Domain\Model\Category $category */
        foreach ($targetCategories as $category) {
            if ($category instanceof \TYPO3\CMS\Extbase\Domain\Model\Category) {
                $sysCategoriesList[] = $category->getUid();
            }
        }

        if (count($sysCategoriesList)) {

            // 2. set leftJoin over categories
            $leftJoin = '
                LEFT JOIN sys_category_record_mm AS sys_category_record_mm 
                    ON tx_rkwoutcome_domain_model_surveyconfiguration.uid=sys_category_record_mm.uid_foreign 
                    AND sys_category_record_mm.tablenames = \'tx_rkwoutcome_domain_model_surveyconfiguration\' 
                    AND sys_category_record_mm.fieldname = \'target_category\'
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
                    sys_category.uid IN(' . implode(',', $sysCategoriesList) . ')
                    AND ' . implode(' AND ', $constraints) .
                QueryTypo3::getWhereClauseForEnableFields('tx_rkwoutcome_domain_model_surveyconfiguration') .
                QueryTypo3::getWhereClauseForDeleteFields('tx_rkwoutcome_domain_model_surveyconfiguration')
                ;

            // build final query
            $query = $this->createQuery();
            $query->getQuerySettings()->setRespectStoragePage(false);
            $query->statement(
                $finalStatement . '
                LIMIT 1'
            );

            /** @var \RKW\RkwOutcome\Domain\Model\SurveyConfiguration $surveyConfiguration */
            return $query->execute()->getFirst();

        }

        return null;

    }


    /**
     * Finds a survey configuration matching the given identifier.
     *
     * @param \RKW\RkwEvents\Domain\Model\Event $event
     *
     * @return object|null The object for the identifier if it is known, or NULL
     */
    public function findByEvent(\RKW\RkwEvents\Domain\Model\Event $event)
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
     * @param \RKW\RkwBasics\Domain\Model\TargetGroup $targetGroup
     *
     * @return object|null The object for the identifier if it is known, or NULL
     */
    public function findByEventAndTargetGroup(\RKW\RkwEvents\Domain\Model\Event $event, \RKW\RkwBasics\Domain\Model\TargetGroup $targetGroup)
    {
        $query = $this->createQuery();

        $query->matching(
            $query->logicalAnd(
                $query->equals('event', $event),
                $query->equals('targetGroup', $targetGroup)
            )
        );

        $query->setLimit(1);

        return $query->execute()->getFirst();
    }

}