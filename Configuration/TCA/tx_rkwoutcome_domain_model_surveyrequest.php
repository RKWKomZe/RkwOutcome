<?php
return [
    'ctrl' => [
        'title'	=> 'LLL:EXT:rkw_outcome/Resources/Private/Language/locallang_db.xlf:tx_rkwoutcome_domain_model_surveyrequest',
        'label' => '',
        'label_userFunc' => \RKW\RkwOutcome\Utilities\TCA::class . '->surveyRequestTitle',
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
        'searchFields' => 'order, event_reservation, process_type, survey, notified_tstamp',
        'iconfile' => 'EXT:rkw_outcome/Resources/Public/Icons/tx_rkwoutcome_domain_model_surveyrequest.gif'
    ],
    'interface' => [
        'showRecordFieldList' => 'sys_language_uid, l10n_parent, l10n_diffsource, hidden, process_type, order, event_reservation, order_subject, event_reservation_subject, survey, target_group, notified_tstamp',
    ],
    'types' => [
        '1' => ['showitem' => 'sys_language_uid, l10n_parent, l10n_diffsource, hidden, process_type, order, event_reservation, order_subject, event_reservation_subject, survey, target_group, notified_tstamp, --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access, starttime, endtime, access_restricted'],
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
            'exclude' => true,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.l18n_parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['', 0],
                ],
                'foreign_table' => 'tx_rkwoutcome_domain_model_surveyrequest',
                'foreign_table_where' => 'AND tx_rkwoutcome_domain_model_surveyrequest.pid=###CURRENT_PID### AND tx_rkwoutcome_domain_model_surveyrequest.sys_language_uid IN (-1,0)',
            ],
        ],
        'l10n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'hidden' => [
            'exclude' => false,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.hidden',
            'config' => [
                'type' => 'check',
                'items' => [
                    '1' => [
                        '0' => 'LLL:EXT:lang/locallang_core.xlf:labels.enabled'
                    ]
                ],
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

        'frontend_user' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:rkw_outcome/Resources/Private/Language/locallang_db.xlf:tx_rkwoutcome_domain_model_surveyrequest.frontendUser',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'fe_users',
                'foreign_table_where' => 'AND fe_users.disable = 0 AND fe_users.deleted = 0 ORDER BY username ASC',
                'minitems' => 1,
                'maxitems' => 1,
                'readOnly' => true
            ],
        ],

        'order' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:rkw_outcome/Resources/Private/Language/locallang_db.xlf:tx_rkwoutcome_domain_model_surveyrequest.order',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_rkwshop_domain_model_order',
                'foreign_table_where' => 'AND tx_rkwshop_domain_model_order.deleted = 0',
                'minitems' => 1,
                'maxitems' => 1,
                'readOnly' => true
            ],
            'displayCond' => 'FIELD:process_type:=:RKW\RkwShop\Domain\Model\Order',
        ],

        'event_reservation' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:rkw_outcome/Resources/Private/Language/locallang_db.xlf:tx_rkwoutcome_domain_model_surveyrequest.eventReservation',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_rkwevents_domain_model_eventreservation',
                'foreign_table_where' => 'AND tx_rkwevents_domain_model_eventreservation.deleted = 0',
                'minitems' => 1,
                'maxitems' => 1,
                'readOnly' => true
            ],
            'displayCond' => 'FIELD:process_type:=:RKW\RkwEvents\Domain\Model\EventReservation',
        ],

        'process_type' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:rkw_outcome/Resources/Private/Language/locallang_db.xlf:tx_rkwoutcome_domain_model_surveyrequest.processType',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim, required',
                'readOnly' => true
            ],
        ],

        'order_subject' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:rkw_outcome/Resources/Private/Language/locallang_db.xlf:tx_rkwoutcome_domain_model_surveyrequest.orderSubject',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_rkwshop_domain_model_product',
                'foreign_table_where' => 'AND tx_rkwshop_domain_model_product.deleted = 0 ORDER BY title ASC',
                'minitems' => 1,
                'maxitems' => 1,
                'readOnly' => true
            ],
            'displayCond' => 'FIELD:process_type:=:RKW\RkwShop\Domain\Model\Order',
        ],

        'event_reservation_subject' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:rkw_outcome/Resources/Private/Language/locallang_db.xlf:tx_rkwoutcome_domain_model_surveyrequest.eventReservationSubject',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_rkwevents_domain_model_event',
                'foreign_table_where' => 'AND tx_rkwevents_domain_model_event.deleted = 0 ORDER BY title ASC',
                'minitems' => 1,
                'maxitems' => 1,
                'readOnly' => true
            ],
            'displayCond' => 'FIELD:process_type:=:RKW\RkwEvents\Domain\Model\EventReservation',
        ],

        'survey' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:rkw_outcome/Resources/Private/Language/locallang_db.xlf:tx_rkwoutcome_domain_model_surveyrequest.survey',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_rkwsurvey_domain_model_survey',
                'foreign_table_where' => ' AND ((\'###PAGE_TSCONFIG_IDLIST###\' <> \'0\' AND FIND_IN_SET(tx_rkwsurvey_domain_model_survey.pid,\'###PAGE_TSCONFIG_IDLIST###\')) OR (\'###PAGE_TSCONFIG_IDLIST###\' = \'0\')) AND tx_rkwsurvey_domain_model_survey.hidden = 0 AND tx_rkwsurvey_domain_model_survey.deleted = 0',
                'size' => 1,
                'minitems' => 0,
                'maxitems' => 1,
                'readOnly' => true,
            ],
        ],

        'notified_tstamp' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:rkw_outcome/Resources/Private/Language/locallang_db.xlf:tx_rkwoutcome_domain_model_surveyrequest.notifiedTstamp',
            'config' => [
                'type'       => 'input',
                'renderType' => 'inputDateTime',
                'eval'       => 'datetime,int',
                'default'    => 0,
                'readOnly' => true
            ],
        ],
    ],
];
