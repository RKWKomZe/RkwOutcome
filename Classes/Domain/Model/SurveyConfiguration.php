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

use RKW\RkwShop\Domain\Model\Product;
use RKW\RkwSurvey\Domain\Model\Survey;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

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
     * @var \RKW\RkwShop\Domain\Model\Product|null
     * @todo Das ist eine fixe Abhängigkeit
     */
    protected ?Product $product = null;


    /**
     * @var \RKW\RkwSurvey\Domain\Model\Survey|null
     */
    protected ?Survey $survey = null;


    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\Category>|null
     */
    protected ?ObjectStorage $targetGroup = null;


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
     * Returns the product
     *
     * @return \RKW\RkwShop\Domain\Model\Product|null $product
     */
    public function getProduct(): ?Product
    {
        return $this->product;
    }


    /**
     * Sets the product
     *
     * @param \RKW\RkwShop\Domain\Model\Product $product
     * @return void
     */
    public function setProducts(Product $product): void
    {
        $this->product = $product;
    }


    /**
     * Returns the survey
     *
     * @return \RKW\RkwSurvey\Domain\Model\Survey|null $survey
     */
    public function getSurvey():? Survey
    {
        return $this->survey;
    }


    /**
     * Sets the survey
     *
     * @param \RKW\RkwSurvey\Domain\Model\Survey $survey
     * @return void
     */
    public function setSurvey(Survey $survey): void
    {
        $this->survey = $survey;
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
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\Category> $targetGroup
     * @return void
     */
    public function setTargetGroup(ObjectStorage $targetGroup): void
    {
        $this->targetGroup = $targetGroup;
    }

    /**
     * @todo Normalerweise hat man bei ObjectStorages auch noch ein add und ein remove für einzelne Objekte
     */
}
