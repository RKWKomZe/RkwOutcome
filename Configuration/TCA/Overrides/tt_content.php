<?php
defined('TYPO3_MODE') || die('Access denied.');

//=================================================================
// Register Plugins
//=================================================================
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'RKW.RkwOutcome',
    'Outcome',
    'RKW Outcome: Fragebogen'
);

//=================================================================
// Add Flexform
//=================================================================
//$extensionName = strtolower(\TYPO3\CMS\Core\Utility\GeneralUtility::underscoredToUpperCamelCase('rkw_survey'));
//$pluginName = strtolower('Survey');
//$pluginSignature = $extensionName . '_' . $pluginName;
//$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][$pluginSignature] = 'layout,select_key,pages';
//$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$pluginSignature] = 'pi_flexform';
//\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
//    $pluginSignature,
//    'FILE:EXT:rkw_survey/Configuration/FlexForms/Survey.xml'
//);
