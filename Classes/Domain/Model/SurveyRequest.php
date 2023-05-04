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

use RKW\RkwEvents\Domain\Model\Event;
use RKW\RkwEvents\Domain\Model\EventReservation;
use RKW\RkwRegistration\Domain\Model\FrontendUser;
use RKW\RkwShop\Domain\Model\Order;
use RKW\RkwShop\Domain\Model\Product;
use TYPO3\CMS\Extbase\Domain\Model\Category;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;


/**
 * Class SurveyRequest
 *
 * @author Christian Dilger <c.dilger@addorange.de>
 * @copyright Rkw Kompetenzzentrum
 * @package RKW_RkwOutcome
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class SurveyRequest extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{
    /**
     * @var \RKW\RkwRegistration\Domain\Model\FrontendUser|null
     */
    protected $frontendUser = null;


    /**
     * @var \RKW\RkwShop\Domain\Model\Order|null
     */
    protected $order = null;


    /**
     * @var \RKW\RkwEvents\Domain\Model\EventReservation|null
     */
    protected $eventReservation = null;


    /**
     * @var string
     */
     protected $processType = '';


    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\Category>|null
     */
    protected $targetGroup = null;


    /**
     * @var int
     */
    protected $notifiedTstamp = 0;


    /**
     * @var \RKW\RkwShop\Domain\Model\Product|null
     */
    protected $orderSubject = null;


    /**
     * @var \RKW\RkwEvents\Domain\Model\Event|null
     */
    protected $eventReservationSubject = null;


    /**
     * @var \RKW\RkwOutcome\Domain\Model\SurveyConfiguration|null
     */
    protected $surveyConfiguration = null;


    /**
     * @var bool
     */
    protected $deleted = false;


    /**
     * __construct
     */
    public function __construct()
    {
        //Do not remove the next line: It would break the functionality
        $this->initStorageObjects();
    }


    /**
     * Initializes all ObjectStorage properties
     * Do not modify this method!
     * It will be rewritten on each save in the extension builder
     * You may modify the constructor of this class instead
     *
     * @return void
     */
    protected function initStorageObjects(): void
    {
        $this->targetGroup = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
    }


    /**
     * Returns the deleted value
     *
     * @return bool
     * @api
     */
    public function getDeleted(): bool
    {
        return $this->deleted;
    }


    /**
     * Sets the deleted value
     *
     * @param bool $deleted
     * @return void
     * @api
     */
    public function setDeleted(bool $deleted): void
    {
        $this->deleted = $deleted;
    }


    /**
     * Returns the order
     *
     * @return \RKW\RkwShop\Domain\Model\Order
     */
    public function getOrder():? Order
    {
        return $this->order;
    }


    /**
     * Sets the order
     *
     * @param \RKW\RkwShop\Domain\Model\Order $order
     * @return void
     */
    public function setOrder(Order $order): void
    {
        $this->order = $order;
    }


    /**
     * @return \RKW\RkwEvents\Domain\Model\EventReservation
     */
    public function getEventReservation(): ? EventReservation
    {
        return $this->eventReservation;
    }


    /**
     * @param \RKW\RkwEvents\Domain\Model\EventReservation $eventReservation
     */
    public function setEventReservation(EventReservation $eventReservation): void
    {
        $this->eventReservation = $eventReservation;
    }


    /**
     * Returns the process type
     *
     * @return string
     */
    public function getProcessType(): string
    {
        return $this->processType;
    }


    /**
     * Sets the processType
     *
     * @param string $processType
     * @return void
     */
    public function setProcessType(string $processType): void
    {
        $this->processType = $processType;
    }


    /**
     * Returns the frontendUser
     *
     * @return \RKW\RkwRegistration\Domain\Model\FrontendUser
     */
    public function getFrontendUser():? FrontendUser
    {
        return $this->frontendUser;
    }


    /**
     * Sets the frontendUser
     *
     * @param \RKW\RkwRegistration\Domain\Model\FrontendUser $frontendUser
     * @return void
     */
    public function setFrontendUser(FrontendUser $frontendUser): void
    {
        $this->frontendUser = $frontendUser;
    }


    /**
     * Returns the notifiedTstamp
     *
     * @return int
     */
    public function getNotifiedTstamp(): int
    {
        return $this->notifiedTstamp;
    }


    /**
     * Sets the notifiedTstamp
     *
     * @param int $notifiedTstamp
     * @return void
     */
    public function setNotifiedTstamp(int $notifiedTstamp): void
    {
        $this->notifiedTstamp = $notifiedTstamp;
    }


    /**
     * Returns the orderSubject
     *
     * @return \RKW\RkwShop\Domain\Model\Product
     */
    public function getOrderSubject():? Product
    {
        return $this->orderSubject;
    }


    /**
     * Sets the orderSubject
     *
     * @param \RKW\RkwShop\Domain\Model\Product $orderSubject
     * @return void
     */
    public function setOrderSubject(Product $orderSubject): void
    {
        $this->orderSubject = $orderSubject;
    }


    /**
     * Returns the eventReservationSubject
     *
     * @return \RKW\RkwEvents\Domain\Model\Event
     */
    public function getEventReservationSubject():? Event
    {
        return $this->eventReservationSubject;
    }


    /**
     * Sets the eventReservationSubject
     *
     * @param \RKW\RkwEvents\Domain\Model\Event $eventReservationSubject
     */
    public function setEventReservationSubject(Event $eventReservationSubject): void
    {
        $this->eventReservationSubject = $eventReservationSubject;
    }


    /**
     * Returns the surveyConfiguration
     *
     * @return \RKW\RkwOutcome\Domain\Model\SurveyConfiguration
     */
    public function getSurveyConfiguration():? SurveyConfiguration
    {
        return $this->surveyConfiguration;
    }


    /**
     * Sets the surveyConfiguration
     *
     * @param \RKW\RkwOutcome\Domain\Model\SurveyConfiguration $surveyConfiguration
     * @return void
     */
    public function setSurveyConfiguration(SurveyConfiguration $surveyConfiguration): void
    {
        $this->surveyConfiguration = $surveyConfiguration;
    }


    /**
     * Returns the targetGroup
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\Category> $targetGroup
     */
    public function getTargetGroup(): ObjectStorage
    {
        return $this->targetGroup;
    }


    /**
     * Sets the targetGroup
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $targetGroup
     * @return void
     */
    public function setTargetGroup(ObjectStorage $targetGroup): void
    {
        $this->targetGroup = $targetGroup;
    }


    /**
     * Adds a targetGroup
     *
     * @param \TYPO3\CMS\Extbase\Domain\Model\Category $targetGroup
     * @return void
     */
    public function addTargetGroup(Category $targetGroup): void
    {
        $this->targetGroup->attach($targetGroup);
    }


    /**
     * Removes a targetGroup
     *
     * @param \TYPO3\CMS\Extbase\Domain\Model\Category $targetGroupToRemove
     * @return void
     */
    public function removeTargetGroup(Category $targetGroupToRemove): void
    {
        $this->targetGroup->detach($targetGroupToRemove);
    }

}
