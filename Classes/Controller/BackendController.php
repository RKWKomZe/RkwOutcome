<?php
namespace RKW\RkwOutcome\Controller;

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

use Madj2k\Postmaster\Domain\Repository\BounceMailRepository;
use Madj2k\Postmaster\Domain\Repository\ClickStatisticsRepository;
use Madj2k\Postmaster\Domain\Repository\MailingStatisticsRepository;
use Madj2k\Postmaster\Domain\Repository\QueueMailRepository;
use Madj2k\Postmaster\Domain\Repository\QueueRecipientRepository;

/**
 * BackendController
 *
 * @author Christian Dilger <c.dilger@addorange.de>
 * @copyright Rkw Kompetenzzentrum
 * @package RKW_RkwOutcome
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class BackendController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{

    /**
     * @var \Madj2k\Postmaster\Domain\Repository\MailingStatisticsRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected MailingStatisticsRepository $mailingStatisticsRepository;


    /**
     * @var \Madj2k\Postmaster\Domain\Repository\ClickStatisticsRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected ClickStatisticsRepository $clickStatisticsRepository;


    /**
     * @var \Madj2k\Postmaster\Domain\Repository\BounceMailRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected BounceMailRepository $bounceMailRepository;


    /**
     * @var \Madj2k\Postmaster\Domain\Repository\QueueMailRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected QueueMailRepository $queueMailRepository;


    /**
     * @var \Madj2k\Postmaster\Domain\Repository\QueueRecipientRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected QueueRecipientRepository $queueRecipientRepository;


    /**
     * Shows statistics
     *
     * @param int $timeFrame
     * @param int $mailType
     * @return void
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     */
    public function statisticsAction(int $timeFrame = 0, int $mailType = -1)
    {

//        $period = TimePeriodUtility::getTimePeriod($timeFrame);
//        $mailingStatisticsList = $this->mailingStatisticsRepository->findByTstampFavSendingAndType(
//            $period['from'],
//            $period['to'],
//            $mailType
//        );
//
//        $mailTypeList = [];
//        if (is_array($this->settings['types'])) {
//            foreach ($this->settings['types'] as $key => $value)
//                $mailTypeList[$key] = ucFirst($value);
//        }
//        asort($mailTypeList);
//

        $data = ['frontend_users' => 2];

        $this->view->assignMultiple(
            [
                'data' => $data,
            ]
        );
    }

}
