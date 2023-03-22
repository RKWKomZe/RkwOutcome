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

//    /**
//     * @var string $extkey Extension key for loggin information
//     */
//    private $extkey = 'pb_social';

    private function initializeTrait()
    {
        /** @var $logger Logger */
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
    }

//    /**
//     * @param string $message
//     * @param integer $ttContentUid actual plugin uid
//     * @param integer $ttContentPid actual uid of page, plugin is located
//     * @param string $type Name of social media network
//     * @param integer $locationInCode timestamp to find in code
//     * @return string
//     */
//    private function initializeMessage($message, $ttContentUid, $ttContentPid, $type, $locationInCode){
//        return $this->extkey . " - flexform $ttContentUid on page $ttContentPid tab ".$type. ": $locationInCode " . strval($message);
//    }
//
    /**
     * @param string $message
     * @return void
     */
    public function logError(string $message):void
    {
        $this->initializeTrait();
        //write log to file according to log level
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
        //write log to file according to log level
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
        //write log to file according to log level
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
        //write log to file according to log level
        $this->logger->log(
            LogLevel::INFO,
            $message
        );
    }

}