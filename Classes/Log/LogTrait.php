<?php
namespace RKW\RkwOutcome\Log;

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

use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * LogTrait
 *
 * @author Christian Dilger <c.dilger@addorange.de>
 * @copyright Rkw Kompetenzzentrum
 * @package RKW_RkwOutcome
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
trait LogTrait
{

    /**
     * @var \TYPO3\CMS\Core\Log\Logger
     */
    protected ?Logger $logger = null;


    /**
     * initializeTrait
     *
     * @return void
     */
    private function initializeTrait(): void
    {
        /** @var \TYPO3\CMS\Core\Log\Logger $logger */
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
    }


    /**
     * @param string $message
     * @return void
     */
    public function logError(string $message): void
    {
        $this->initializeTrait();
        $this->logger->log(
            LogLevel::ERROR,
            $message
        );
    }


    /**
     * @param string $message
     * @return void
     */
    public function logDebug(string $message): void
    {
        $this->initializeTrait();
        $this->logger->log(
            LogLevel::DEBUG,
            $message
        );
    }


    /**
     * @param string $message
     * @return void
     */
    public function logWarning(string $message): void
    {
        $this->initializeTrait();
        $this->logger->log(
            LogLevel::WARNING,
            $message
        );
    }


    /**
     * @param string $message
     * @return void
     */
    public function logInfo(string $message): void
    {
        $this->initializeTrait();
        $this->logger->log(
            LogLevel::INFO,
            $message
        );
    }

}
