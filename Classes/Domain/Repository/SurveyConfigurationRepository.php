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
     * @param \RKW\RkwShop\Domain\Model\Product $product
     * @param \RKW\RkwBasics\Domain\Model\TargetGroup $targetGroup
     *
     * @return object|null The object for the identifier if it is known, or NULL
     */
    public function findByProductAndTargetGroup(\RKW\RkwShop\Domain\Model\Product $product, \RKW\RkwBasics\Domain\Model\TargetGroup $targetGroup)
    {
        $query = $this->createQuery();

        $query->matching(
            $query->logicalAnd(
                $query->equals('product', $product),
                $query->equals('targetGroup', $targetGroup)
            )
        );

        $query->setLimit(1);

        /** @var \RKW\RkwOutcome\Domain\Model\SurveyConfiguration $surveyConfiguration */
        return $query->execute()->getFirst();
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