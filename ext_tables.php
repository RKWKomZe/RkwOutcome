<?php
defined('TYPO3_MODE') || die('Access denied.');

call_user_func(
    function($extKey)
    {

        //=================================================================
        // Register BackendModule
        //=================================================================
        // "ExtensionUtility::registerModule" is allowed here:
        // https://docs.typo3.org/m/typo3/reference-coreapi/master/en-us/ExtensionArchitecture/ConfigurationFiles/Index.html#id4

        if (TYPO3_MODE === 'BE') {

            \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
                'RKW.RkwOutcome',
                'web',	 // Make module a submodule of 'web'
                'evaluation',	// Submodule key
                '',						// Position
                array(
                    'Backend' => 'statistics',
                ),
                array(
                    'access' => 'user,group',
                    'icon'   => 'EXT:' . $extKey . '/ext_icon.gif',
                    'labels' => 'LLL:EXT:' . $extKey . '/Resources/Private/Language/locallang_backend.xlf',
                )
            );

        }


        //=================================================================
        // Add tables
        //=================================================================
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages(
            'tx_rkwoutcome_domain_model_surveyconfiguration'
        );


    },
    $_EXTKEY
);

