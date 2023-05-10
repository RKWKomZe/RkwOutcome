<?php
defined('TYPO3_MODE') || die('Access denied.');

call_user_func(
    function($extKey) {
        //=================================================================
        // Add TypoScript
        //=================================================================
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
            $extKey,
            'Configuration/TypoScript',
            'RKW Outcome'
        );
    }, 'rkw_outcome'
);
