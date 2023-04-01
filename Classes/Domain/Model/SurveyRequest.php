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


/**
 * SurveyRequest
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
     * @var \RKW\RkwRegistration\Domain\Model\FrontendUser|null
     */
    protected $frontendUser;


    /**
     * process
     *
     * @var \RKW\RkwShop\Domain\Model\Order|null
     *
     */
    protected $process;


    /**
     * processType
     *
     * @var string
     */
     protected $processType;



    /**
     * TargetGroup
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\Category>
     */
    protected $targetGroup;


    /**
     * notifiedTstamp
     *
     * @var int
     */
    protected $notifiedTstamp = 0;


    /**
     * processSubject
     *
     * @var \RKW\RkwShop\Domain\Model\Product|null
     *
     */
    protected $processSubject;


    /**
     * Returns the survey
     *
     * @var \RKW\RkwSurvey\Domain\Model\Survey|null
     */
    protected $survey;


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
    protected function initStorageObjects()
    {
        $this->targetGroup = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
    }


    /**
     * Returns the process
     *
     * @return \RKW\RkwShop\Domain\Model\Order|null
     */
    public function getProcess()
    {
        return $this->process;
    }


    /**
     * Sets the process
     *
     * @param \RKW\RkwShop\Domain\Model\Order $process
     * @return void
     */
    public function setProcess(\RKW\RkwShop\Domain\Model\Order $process): void
    {
        $this->process = $process;
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
    public function getFrontendUser()
    {
        return $this->frontendUser;
    }


    /**
     * Sets the frontendUser
     *
     * @param \RKW\RkwRegistration\Domain\Model\FrontendUser $frontendUser
     * @return void
     */
    public function setFrontendUser(\RKW\RkwRegistration\Domain\Model\FrontendUser $frontendUser): void
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
     * Returns the processSubject
     *
     * @return \RKW\RkwShop\Domain\Model\Product|null $processSubject
     */
    public function getProcessSubject()
    {
        return $this->processSubject;
    }


    /**
     * Sets the processSubject
     *
     * @param \RKW\RkwShop\Domain\Model\Product $processSubject
     * @return void
     */
    public function setProcessSubject(\RKW\RkwShop\Domain\Model\Product $processSubject): void
    {
        $this->processSubject = $processSubject;
    }

    /**
     * Returns the survey
     *
     * @return \RKW\RkwSurvey\Domain\Model\Survey|null
     */
    public function getSurvey()
    {
        return $this->survey;
    }


    /**
     * Sets the survey
     *
     * @param \RKW\RkwSurvey\Domain\Model\Survey $survey
     * @return void
     */
    public function setSurvey(\RKW\RkwSurvey\Domain\Model\Survey $survey): void
    {
        $this->survey = $survey;
    }

    /**
     * Returns the targetGroup
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\Category> $targetGroup
     */
    public function getTargetGroup()
    {
        return $this->targetGroup;
    }

    /**
     * Sets the targetGroup
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\Category> $targetGroup
     * @return void
     */
    public function setTargetGroup(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $targetGroup)
    {
        $this->targetGroup = $targetGroup;
    }

    /**
     * Adds a Category
     *
     * @param \TYPO3\CMS\Extbase\Domain\Model\Category $targetGroup
     * @return void
     */
    public function addTargetGroup(\TYPO3\CMS\Extbase\Domain\Model\Category $targetGroup): void
    {
        $this->targetGroup->attach($targetGroup);
    }

    /**
     * Removes a Category
     *
     * @param \TYPO3\CMS\Extbase\Domain\Model\Category $targetGroupToRemove
     * @return void
     */
    public function removeTargetGroup(\TYPO3\CMS\Extbase\Domain\Model\Category $targetGroupToRemove)
    {
        $this->targetGroup->detach($targetGroupToRemove);
    }


}