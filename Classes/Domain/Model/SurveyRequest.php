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

use Madj2k\FeRegister\Domain\Model\FrontendUser;
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
     * @var \Madj2k\FeRegister\Domain\Model\FrontendUser|null
     */
    protected ?FrontendUser $frontendUser = null;


    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\Category>|null
     */
    protected ?ObjectStorage $targetGroup = null;


    /**
     * @var int
     */
    protected int $notifiedTstamp = 0;


    /**
     * @var \RKW\RkwOutcome\Domain\Model\SurveyConfiguration|null
     */
    protected ?SurveyConfiguration $surveyConfiguration = null;


    /**
     * @var string
     */
    protected string $process = '';


    /**
     * @var array
     */
    protected array $processUnserialized = [];


    /**
     * @var string
     */
    protected string $processSubject = '';


    /**
     * @var array
     */
    protected array $processSubjectUnserialized = [];


    /**
     * @var bool
     */
    protected bool $deleted = false;


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
     * Returns the process
     *
     * @return array $process
     */
    public function getProcess(): array
    {
        if ($this->processUnserialized) {
            return $this->processUnserialized;
        }

        return ($this->process ? unserialize($this->process) : []);
    }


    /**
     * Sets the process
     *
     * @param array $process
     * @return void
     */
    public function setProcess(array $process): void
    {
        $this->processUnserialized = $process;
        $this->process = serialize($process);
    }


    /**
     * Returns the frontendUser
     *
     * @return \Madj2k\FeRegister\Domain\Model\FrontendUser
     */
    public function getFrontendUser(): ?FrontendUser
    {
        return $this->frontendUser;
    }


    /**
     * Sets the frontendUser
     *
     * @param \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser
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
     * Returns the processSubject
     *
     * @return array $processSubject
     */
    public function getProcessSubject(): array
    {
        if ($this->processSubjectUnserialized) {
            return $this->processSubjectUnserialized;
        }

        return ($this->processSubject ? unserialize($this->processSubject) : []);
    }


    /**
     * Sets the processSubject
     *
     * @param array $processSubject
     * @return void
     */
    public function setProcessSubject(array $processSubject): void
    {
        $this->processSubjectUnserialized = $processSubject;
        $this->processSubject = serialize($processSubject);
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
