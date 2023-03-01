<?php

namespace RKW\RkwOutcome\Domain\Model;
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

use RKW\RkwShop\Domain\Model\Order;

/**
 * Approval
 *
 * @author Christian Dilger <c.dilger@addorange.de>
 * @copyright Rkw Kompetenzzentrum
 * @package RKW_RkwOutcome
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class SurveyRequest extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{

    /**
     * frontendUser
     *
     * @var \RKW\RkwShop\Domain\Model\FrontendUser
     */
    protected $frontendUser = null;

    /**
     * order
     *
     * @var \RKW\RkwShop\Domain\Model\Order
     */
    protected $order = null;

    /**
     * targetGroup
     *
     * @var \RKW\RkwBasics\Domain\Model\TargetGroup
     */
    protected $targetGroup;

    /**
     * Returns the order
     *
     * @return \RKW\RkwShop\Domain\Model\Order|null
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Sets the order
     *
     * @param \RKW\RkwShop\Domain\Model\Order
     * @return void
     */
    public function setOrder(Order $order): void
    {
        $this->order = $order;
    }

    /**
     * Returns the frontendUser
     *
     * @return \RKW\RkwShop\Domain\Model\FrontendUser $frontendUser
     */
    public function getFrontendUser()
    {
        return $this->frontendUser;
    }

    /**
     * Sets the frontendUser
     *
     * @param \RKW\RkwShop\Domain\Model\FrontendUser $frontendUser
     * @return void
     */
    public function setFrontendUser(\RKW\RkwShop\Domain\Model\FrontendUser $frontendUser)
    {
        $this->frontendUser = $frontendUser;
    }

    /**
     * Returns the targetGroup
     *
     * @return \RKW\RkwBasics\Domain\Model\TargetGroup
     */
    public function getTargetGroup()
    {
        return $this->targetGroup;
    }

    /**
     * Sets the targetGroup
     *
     * @param \RKW\RkwBasics\Domain\Model\TargetGroup $targetGroup
     * @return void
     */
    public function setTargetGroup(\RKW\RkwBasics\Domain\Model\TargetGroup $targetGroup)
    {
        $this->targetGroup = $targetGroup;
    }

}