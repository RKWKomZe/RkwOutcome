<?php

namespace RKW\RkwOutcome\UserFunctions;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Extbase\Utility\LocalizationUtility;


/**
 * Class OptionLabels
 *
 * @author Christian Dilger <c.dilger@addorange.de>
 * @copyright Rkw Kompetenzzentrum
 * @package RKW_RkwOutcome
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class OptionLabels
{
    /**
     * Fetches labels for surveys
     *
     * @params array &$params
     * @params object $pObj
     * @return void
     */
    public static function getSurveyNamesWithUid(array &$params, $pObj): void
    {
        // override given values
        foreach ($params['items'] as &$item) {
            $item[0] .= ' (Uid: ' . $item[1] . ')';
        }
    }

}
