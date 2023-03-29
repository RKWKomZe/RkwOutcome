<?php

//=================================================================
// Add Category
//=================================================================
// Add an extra categories selection field to the pages table
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::makeCategorizable(
    'examples',
    'tx_rkwoutcome_domain_model_surveyrequest',
    // Do not use the default field name ("categories") for pages, tt_content, sys_file_metadata, which is already used
    'target_category',
    array(
        // Set a custom label
        'label' => 'LLL:EXT:examples/Resources/Private/Language/locallang.xlf:additional_categories',
        // This field should not be an exclude-field
        'exclude' => FALSE,
        // Override generic configuration, e.g. sort by title rather than by sorting
        'fieldConfiguration' => [
//            'treeConfig' => [
//                'rootUid' => 147
//            ],
//            'foreign_table_where' => ' AND ((\'###PAGE_TSCONFIG_IDLIST###\' <> \'0\' AND FIND_IN_SET(sys_category.pid,\'###PAGE_TSCONFIG_IDLIST###\')) OR (\'###PAGE_TSCONFIG_IDLIST###\' = \'0\')) AND sys_category.sys_language_uid IN (-1, 0) ORDER BY sys_category.title ASC',
//            'type' => 'select',
//            'renderType' => 'selectSingle',
//            'itemsProcFunc' => 'RKW\\RkwOutcome\\UserFunctions\\TcaProcFunc->getSelectedCategories',
        ],
        // string (keyword), see TCA reference for details
        'l10n_mode' => 'exclude',
        // list of keywords, see TCA reference for details
        'l10n_display' => 'hideDiff',
    )
);

//  @todo: Does not work as TCEFORM.tx_rkwoutcome_domain_model_surveyconfiguration.target_category.config.treeConfig.rootUid = 147 in 50-categories.typoscript!?
$GLOBALS['TCA']['tx_rkwoutcome_domain_model_surveyrequest']['columns']['target_category']['config']['treeConfig']['rootUid'] = 147;

