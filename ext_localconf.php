<?php
defined('TYPO3_MODE') || die('Access denied.');

call_user_func(
    function($extKey)
    {

        //=================================================================
        // Configure Plugins
        //=================================================================
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
            'RKW.' . $extKey,
            'SurveyRequest',
            array(
                'SurveyRequest' => 'show',
            ),

            // non-cacheable actions
            array(
                'SurveyRequest' => 'show',
            )
        );

        //=================================================================
        // Register CommandController
        //=================================================================
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] = 'RKW\RkwOutcome\Controller\SurveyRequestCommandController';

        //=================================================================
        // Register TCA evaluation to be available in 'eval' of TCA
        //=================================================================

        //=================================================================
        // Register Hooks
        //=================================================================

        //=================================================================
        // Register SignalSlots
        //=================================================================
        /**
         * @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher
         */
        $signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);

        $signalSlotDispatcher->connect(
            \RKW\RkwShop\Orders\OrderManager::class,
            \RKW\RkwShop\Orders\OrderManager::SIGNAL_AFTER_ORDER_CREATED_USER,
            \RKW\RkwOutcome\Manager\SurveyRequestManager::class,
            'createSurveyRequestSignalSlot'
        );

        $signalSlotDispatcher->connect(
            \RKW\RkwOutcome\Manager\SurveyRequestManager::class,
            \RKW\RkwOutcome\Manager\SurveyRequestManager::SIGNAL_FOR_SENDING_MAIL_SURVEYREQUEST,
            \RKW\RkwOutcome\Service\RkwMailService::class,
            'sendMailSurveyRequestToUser'
        );

        //=================================================================
        // Register Logger
        //=================================================================
        $GLOBALS['TYPO3_CONF_VARS']['LOG']['RKW']['RkwOutcome']['writerConfiguration'] = array(

            // configuration for WARNING severity, including all
            // levels with higher severity (ERROR, CRITICAL, EMERGENCY)
            \TYPO3\CMS\Core\Log\LogLevel::INFO => array(
                // add a FileWriter
                'TYPO3\\CMS\\Core\\Log\\Writer\\FileWriter' => array(
                    // configuration for the writer
                    'logFile' => 'typo3temp/var/logs/tx_rkwoutcome.log'
                )
            ),
        );
    },
    $_EXTKEY
);


