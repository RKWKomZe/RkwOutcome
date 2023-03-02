<?php
namespace RKW\RkwOutcome\Manager;

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

use RKW\RkwOutcome\Domain\Model\SurveyRequest;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * SurveyRequestManager
 *
 * @author Christian Dilger <c.dilger@addorange.de>
 * @copyright Rkw Kompetenzzentrum
 * @package RKW_RkwOutcome
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class SurveyRequestManager implements \TYPO3\CMS\Core\SingletonInterface
{

    /**
     * @var \RKW\RkwOutcome\Domain\Repository\SurveyRequestRepository
     * @inject
     */
    protected $surveyRequestRepository;

    /**
     * PersistenceManager
     *
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager
     * @inject
     */
    protected $persistenceManager;

    /**
     * Intermediate function for creating survey requests - used by SignalSlot
     *
     * @param \RKW\RkwShop\Domain\Model\BackendUser|array $backendUser
     * @param \RKW\RkwShop\Domain\Model\Order|\RKW\RkwEvents\Domain\Model\Event $process
     * @param array $backendUserForProductMap
     * @return \RKW\RkwOutcome\Domain\Model\SurveyRequest
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     */
    public function createSurveyRequest
    (
//        $backendUser,
        $process
//        $backendUserForProductMap
    ): SurveyRequest
    {
        //  @todo: use a custom Signal in OrderManager->saveOrder to provide FE-User instead of BE-User
        //

        /** @var \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequest */
        $surveyRequest = GeneralUtility::makeInstance(SurveyRequest::class);
        $surveyRequest->setProcess($process);
        //  @todo: Kann evtl. das Setzen des processType per Mutator erfolgen, so dass es hier nicht explizit gesetzt werden muss?
        $surveyRequest->setProcessType(get_class($process));
        $surveyRequest->setFrontendUser($process->getFrontendUser());
        $surveyRequest->setTargetGroup($process->getTargetGroup());

        $this->surveyRequestRepository->add($surveyRequest);
        $this->persistenceManager->persistAll();

        $this->getLogger()->log(
            LogLevel::DEBUG,
            sprintf(
//                'Created surveyRequest for order with id=%s of by frontenduser with id=%s.',
                'Created surveyRequest for process with id=%s of type=%s by frontenduser with id=',
                $process->getUid(),
                get_class($process)
//                $issue->getUid()
            )
        );

        return $surveyRequest;

    }


    /**
     * Returns logger instance
     *
     * @return \TYPO3\CMS\Core\Log\Logger
     */
    protected function getLogger(): Logger
    {
        if (!$this->logger instanceof Logger) {
            $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
        }

        return $this->logger;
    }

}