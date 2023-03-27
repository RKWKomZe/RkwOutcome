<?php

//$GLOBALS['TCA']['tx_rkwoutcome_domain_model_surveyconfiguration']['columns']['target_category']['config']['treeConfig']['rootUid'] = 147;

//=================================================================
// Add Category
//=================================================================
// Add an extra categories selection field to the pages table
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::makeCategorizable(
    'examples',
    'tx_rkwoutcome_domain_model_surveyconfiguration',
    // Do not use the default field name ("categories") for pages, tt_content, sys_file_metadata, which is already used
    'target_category',
    array(
        // Set a custom label
        'label' => 'LLL:EXT:examples/Resources/Private/Language/locallang.xlf:additional_categories',
        // This field should not be an exclude-field
        'exclude' => FALSE,
        // Override generic configuration, e.g. sort by title rather than by sorting
        'fieldConfiguration' => [
            'treeConfig' => [
                'rootUid' => 147
            ],
            'type' => 'select',
//            'renderType' => 'selectSingle',
//            'itemsProcFunc' => 'RKW\\RkwOutcome\\UserFunctions\\TcaProcFunc->getSelectedCategories',
        ],
        // string (keyword), see TCA reference for details
        'l10n_mode' => 'exclude',
        // list of keywords, see TCA reference for details
        'l10n_display' => 'hideDiff',
    )
);

