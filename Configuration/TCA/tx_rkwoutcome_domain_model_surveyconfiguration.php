<?php

return [
    'ctrl' => [
        'title'	=> 'LLL:EXT:rkw_outcome/Resources/Private/Language/locallang_db.xlf:tx_rkwoutcome_domain_model_surveyconfiguration',
        'label' => '',
        'label_userFunc' => \RKW\RkwOutcome\Utility\TCA::class . '->surveyConfigurationTitle',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
        ],
        // do only make requestUpdate, if token-list should be shown on check
        // 'requestUpdate' => 'access_restricted',
        'searchFields' => 'product, event, survey, target_group, mail_text',
        'iconfile' => 'EXT:rkw_outcome/Resources/Public/Icons/tx_rkwoutcome_domain_model_surveyconfiguration.gif'
    ],
    'types' => [
        '1' => ['showitem' => 'process_type, l10n_parent, l10n_diffsource, hidden, product, event, survey, target_group, mail_text, survey_waiting_time'],
    ],
    'columns' => [

        'sys_language_uid' => [
            'exclude' => true,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'special' => 'languages',
                'items' => [
                    [
                        'LLL:EXT:lang/locallang_general.xlf:LGL.allLanguages',
                        -1,
                        'flags-multiple'
                    ]
                ],
                'default' => 0,
            ],
        ],
        'l10n_parent' => [
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.l18n_parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['', 0],
                ],
                'foreign_table' => 'tx_rkwoutcome_domain_model_surveyconfiguration',
                'foreign_table_where' => 'AND tx_rkwoutcome_domain_model_surveyconfiguration.pid=###CURRENT_PID### AND tx_rkwoutcome_domain_model_surveyconfiguration.sys_language_uid IN (-1,0)',
            ],
        ],
        'l10n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'hidden' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.hidden',
            'config' => [
                'type' => 'check',
            ],
        ],
        'starttime' => [
            'exclude' => false,
            //'l10n_mode' => 'mergeIfNotBlank',
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.starttime',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'size' => 13,
                'eval' => 'datetime',
                'default' => 0,
                'behaviour' => [
                    'allowLanguageSynchronization' => true
                ]
            ]
        ],
        'endtime' => [
            'exclude' => false,
            //'l10n_mode' => 'mergeIfNotBlank',
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.endtime',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'size' => 13,
                'eval' => 'datetime',
                'default' => 0,
                'range' => [
                    'upper' => mktime(0, 0, 0, 1, 1, 2038)
                ],
                'behaviour' => [
                    'allowLanguageSynchronization' => true
                ]
            ],
        ],

        'process_type' => [
            'label' => 'LLL:EXT:rkw_outcome/Resources/Private/Language/locallang_db.xlf:tx_rkwoutcome_domain_model_surveyconfiguration.processType',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['LLL:EXT:rkw_outcome/Resources/Private/Language/locallang_db.xlf:tx_rkwoutcome_domain_model_surveyconfiguration.processType.product', \RKW\RkwShop\Domain\Model\Product::class],
                    ['LLL:EXT:rkw_outcome/Resources/Private/Language/locallang_db.xlf:tx_rkwoutcome_domain_model_surveyconfiguration.processType.event', \RKW\RkwEvents\Domain\Model\Event::class],
                ],
                'default' =>  \RKW\RkwShop\Domain\Model\Product::class
            ],
            'onChange' => 'reload',
        ],

        'product' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:rkw_outcome/Resources/Private/Language/locallang_db.xlf:tx_rkwoutcome_domain_model_surveyconfiguration.product',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_rkwshop_domain_model_product',
                'foreign_table_where' => ' AND ((\'###PAGE_TSCONFIG_IDLIST###\' <> \'0\' AND FIND_IN_SET(tx_rkwshop_domain_model_product.pid,\'###PAGE_TSCONFIG_IDLIST###\')) OR (\'###PAGE_TSCONFIG_IDLIST###\' = \'0\')) AND tx_rkwshop_domain_model_product.hidden = 0 AND tx_rkwshop_domain_model_product.deleted = 0 ORDER BY title ASC',
                'size' => 1,
                'minitems' => 0,
                'maxitems' => 1,
            ],
            'displayCond' => 'FIELD:process_type:=:RKW\RkwShop\Domain\Model\Product',
        ],

        'event' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:rkw_outcome/Resources/Private/Language/locallang_db.xlf:tx_rkwoutcome_domain_model_surveyconfiguration.event',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_rkwevents_domain_model_event',
                'foreign_table_where' => ' AND ((\'###PAGE_TSCONFIG_IDLIST###\' <> \'0\' AND FIND_IN_SET(tx_rkwevents_domain_model_event.pid,\'###PAGE_TSCONFIG_IDLIST###\')) OR (\'###PAGE_TSCONFIG_IDLIST###\' = \'0\')) AND tx_rkwevents_domain_model_event.hidden = 0 AND tx_rkwevents_domain_model_event.deleted = 0 ORDER BY start DESC',
                'size' => 1,
                'minitems' => 0,
                'maxitems' => 1,
            ],
            'displayCond' => 'FIELD:process_type:=:RKW\RkwEvents\Domain\Model\Event',
        ],

        'survey' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:rkw_outcome/Resources/Private/Language/locallang_db.xlf:tx_rkwoutcome_domain_model_surveyconfiguration.survey',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'tx_rkwsurvey_domain_model_survey',
                'foreign_table_where' => ' AND ((\'###PAGE_TSCONFIG_IDLIST###\' <> \'0\' AND FIND_IN_SET(tx_rkwsurvey_domain_model_survey.pid,\'###PAGE_TSCONFIG_IDLIST###\')) OR (\'###PAGE_TSCONFIG_IDLIST###\' = \'0\')) AND tx_rkwsurvey_domain_model_survey.hidden = 0 AND tx_rkwsurvey_domain_model_survey.deleted = 0',
                'size' => 5,
                'minitems' => 1,
                'maxitems' => 5,
                'itemsProcFunc' => 'RKW\RkwOutcome\UserFunctions\OptionLabels->getSurveyNamesWithUid',
            ],
        ],

        'mail_text' => [
            'exclude' => false,
            'label' => 'LLL:EXT:rkw_outcome/Resources/Private/Language/locallang_db.xlf:tx_rkwoutcome_domain_model_surveyconfiguration.mailText',
            'config' => [
                'type' => 'text',
                'cols' => '40',
                'rows' => '15',
                'eval' => 'trim',
                'enableRichtext' => true
            ],
        ],

        'survey_waiting_time' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:rkw_outcome/Resources/Private/Language/locallang_db.xlf:tx_rkwoutcome_domain_model_surveyconfiguration.surveyWaitingTime',
            'config' => [
                'type'       => 'input',
                'eval'       => 'int',
                'default'    => 0
            ],
        ],

    ],
];
