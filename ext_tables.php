<?php
defined('TYPO3_MODE') || die('Access denied.');

call_user_func(
    function($extKey)
    {

        //=================================================================
        // Register BackendModule
        //=================================================================

        //=================================================================
        // Add tables
        //=================================================================
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages(
            'tx_rkwoutcome_domain_model_survey'
        );


    },
    $_EXTKEY
);

