<?php

namespace RKW\RkwOutcome\Service;

use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

trait LogTrait
{
    /**
     * @var \TYPO3\CMS\Core\Log\Logger
     */
    protected $logger;


    private function initializeTrait()
    {
        /** @var \TYPO3\CMS\Core\Log\Logger $logger */
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
    }


    /**
     * @param string $message
     * @return void
     */
    public function logError(string $message):void
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
    public function logDebug(string $message):void
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
    public function logWarning(string $message):void
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
    public function logInfo(string $message):void
    {
        $this->initializeTrait();
        $this->logger->log(
            LogLevel::INFO,
            $message
        );
    }

}
