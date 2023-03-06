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
     * @param int $uid The identifier of the object to find
     * @return \RKW\RkwShop\Domain\Model\Product The matching object if found, otherwise NULL
     */
    public function findByProductUid($uid)
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setIncludeDeleted(true);

        $query->matching(
            $query->equals('product', $uid)
        );

        $query->setLimit(1);

        return $query->execute()->getFirst();
    }


    /**
     * Finds a survey configuration matching the given identifier.
     *
     * @param int $uid The identifier of the object to find
     * @return \RKW\RkwEvents\Domain\Model\Event The matching object if found, otherwise NULL
     */
    public function findByEventUid($uid)
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setIncludeDeleted(true);

        $query->matching(
            $query->equals('event', $uid)
        );

        $query->setLimit(1);

        return $query->execute()->getFirst();
    }

}