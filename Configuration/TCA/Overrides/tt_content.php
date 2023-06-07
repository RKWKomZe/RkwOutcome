<?php
defined('TYPO3_MODE') || die('Access denied.');

call_user_func(
    function ($extKey) {

        //=================================================================
        // Register Plugins
        //=================================================================
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
            $extKey,
            'Outcome',
            'RKW Outcome: Fragebogen'
        );

        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
            $extKey,
            'SurveyRequest',
            'RKW Outcome: SurveyRequest'
        );

        //=================================================================
        // Add Flexform
        //=================================================================
        //$extensionName = strtolower(\TYPO3\CMS\Core\Utility\GeneralUtility::underscoredToUpperCamelCase($extKey));
        //$pluginName = strtolower('Survey');
        //$pluginSignature = $extensionName . '_' . $pluginName;
        //$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][$pluginSignature] = 'layout,select_key,pages';
        //$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$pluginSignature] = 'pi_flexform';
        //\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
        //    $pluginSignature,
        //    'FILE:EXT:rkw_survey/Configuration/FlexForms/Survey.xml'
        //);
    },
    'rkw_outcome'
);
