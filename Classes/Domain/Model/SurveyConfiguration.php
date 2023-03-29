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
 * SurveyConfiguration
 *
 * @author Christian Dilger <c.dilger@addorange.de>
 * @copyright Rkw Kompetenzzentrum
 * @package RKW_RkwOutcome
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class SurveyConfiguration extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{


    /**
     * product
     *
     * @var \RKW\RkwShop\Domain\Model\Product|null
     */
    protected $product = null;


    /**
     * survey
     *
     * @var \RKW\RkwSurvey\Domain\Model\Survey|null
     */
    protected $survey = null;


    /**
     * targetGroup
     *
     * @var \RKW\RkwBasics\Domain\Model\TargetGroup|null
     */
    protected $targetGroup = null;


    /**
     * TargetCategory
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\Category>
     */
    protected $targetCategory;



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
        $this->targetCategory = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
    }


    /**
     * Returns the product
     *
     * @return \RKW\RkwShop\Domain\Model\Product|null $product
     */
    public function getProduct()
    {
        return $this->product;
    }


    /**
     * Sets the product
     *
     * @param \RKW\RkwShop\Domain\Model\Product $product
     * @return void
     */
    public function setProducts($product): void
    {
        $this->product = $product;
    }


    /**
     * Returns the survey
     *
     * @return \RKW\RkwSurvey\Domain\Model\Survey|null $survey
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
    public function setSurvey($survey): void
    {
        $this->survey = $survey;
    }


    /**
     * Returns the targetGroup
     *
     * @return \RKW\RkwBasics\Domain\Model\TargetGroup
     */
    public function getTargetGroup(): \RKW\RkwBasics\Domain\Model\TargetGroup
    {
        return $this->targetGroup;
    }


    /**
     * Sets the targetGroup
     *
     * @param \RKW\RkwBasics\Domain\Model\TargetGroup $targetGroup
     * @return void
     */
    public function setTargetGroup(\RKW\RkwBasics\Domain\Model\TargetGroup $targetGroup): void
    {
        $this->targetGroup = $targetGroup;
    }


    /**
     * Returns the targetCategory
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\Category> $targetCategory
     */
    public function getTargetCategory()
    {
        return $this->targetCategory;
    }

    /**
     * Sets the targetCategory
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\Category> $targetCategory
     * @return void
     */
    public function setTargetCategory(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $targetCategory)
    {
        $this->targetCategory = $targetCategory;
    }

}