<?php

namespace RKW\RkwOutcome\Domain\Model;
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

/**
 * Class Process
 *
 * @author Christian Dilger <c.dilger@addorange.de>
 * @copyright Rkw Kompetenzzentrum
 * @package RKW_RkwOutcome
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @todo Wenn dich richtig verstanden habe, soll Process eine allgemeine Klasse sein für alle möglichen Objekte.
 * Dann würde ich vorschlagen: Diese Klasse erhält nicht das eigentliche Objekt, sondern nur alle relevanten Daten des Objektes.
 * Also: getObjectClassName() getObjectUid(). Für die "Übergabe" schreibt man eine Übergabe-Klasse (ggf. als Utility). Basierend auf dem Klassennamen kann man
 * dann bei Bedarf auch das Repository wieder ein Repository laden und passend zur objectUid das eigentliche Objekt ziehen.
 * Damit wäre diese Klasse hier eine Art Datenspeicher. Nimmt man diesen Datenspeicher und schickt ihn in ein passendes Utility
 * dann kommen die eigentlichen Daten raus.
 * Was Ähnliches macht der MarkerReducer (https://github.com/skroggel/typo3-accelerator/blob/master/Classes/Persistence/MarkerReducer.php).
 * Wir benutzen ihn, um beim Anlegen einer Email für die Warteschlange ein Objekt mit übergeben zu können. Der speichert dann in der Datenbank
 * nur den Klassennamen und die Uid. Beim Auslesen der Email machen wir das Ganze rückwärts und laden das Objekt.
 *
 */
class Process extends \RKW\RkwShop\Domain\Model\Order
{

}
