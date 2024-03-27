<?php
defined('TYPO3_MODE') || die('Access denied.');

call_user_func(
    function($extKey)
    {

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
            \RKW\RkwOutcome\SurveyRequest\SurveyRequestCreator::class,
            'createSurveyRequestSignalSlot'
        );

        $signalSlotDispatcher->connect(
            \RKW\RkwEvents\Controller\EventReservationController::class,
            \RKW\RkwEvents\Controller\EventReservationController::SIGNAL_AFTER_RESERVATION_CREATED_USER,
            \RKW\RkwOutcome\SurveyRequest\SurveyRequestCreator::class,
            'createSurveyRequestSignalSlot'
        );

        $signalSlotDispatcher->connect(
            \RKW\RkwOutcome\SurveyRequest\SurveyRequestProcessor::class,
            \RKW\RkwOutcome\SurveyRequest\SurveyRequestProcessor::SIGNAL_FOR_SENDING_MAIL_SURVEYREQUEST,
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
                    'logFile' => \TYPO3\CMS\Core\Core\Environment::getVarPath()  . '/log/tx_rkwoutcome.log'
                )
            ),
        );
    },
    'rkw_outcome'
);


