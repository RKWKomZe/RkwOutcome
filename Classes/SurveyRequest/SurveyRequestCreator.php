<?php
namespace RKW\RkwOutcome\SurveyRequest;

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
use RKW\RkwOutcome\Exception;
use RKW\RkwRegistration\Domain\Model\FrontendUser;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;

/**
 * Class SurveyRequestCreator
 *
 * @author Christian Dilger <c.dilger@addorange.de>
 * @copyright Rkw Kompetenzzentrum
 * @package RKW_RkwOutcome
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class SurveyRequestCreator extends AbstractSurveyRequest
{

    /**
     * Intermediate function for creating surveyRequests - used by SignalSlot
     *
     * @param \RKW\RkwRegistration\Domain\Model\FrontendUser $frontendUser
     * @param \RKW\RkwShop\Domain\Model\Order|\RKW\RkwEvents\Domain\Model\EventReservation $process
     * @return void
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     */
    public function createSurveyRequestSignalSlot(FrontendUser $frontendUser, $process): void
    {
        try {
            $this->createSurveyRequest($process);
        } catch (\RKW\RkwOutcome\Exception $exception) {
            // do nothing
        }
    }


    /**
     * Creates a survey request
     *
     * @param \TYPO3\CMS\Extbase\DomainObject\AbstractEntity $process
     * @return \RKW\RkwOutcome\Domain\Model\SurveyRequest|null
     * @throws \RKW\RkwOutcome\Exception
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     */
    public function createSurveyRequest (AbstractEntity $process): ?SurveyRequest
    {
        $frontendUser = null;

        try {
            if ($this->isSurveyable($process)) {

                /** @var \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequest */
                $surveyRequest = GeneralUtility::makeInstance(SurveyRequest::class);

                if ($process instanceof \Rkw\RkwShop\Domain\Model\Order) {
                    /** @var \RKW\RkwRegistration\Domain\Model\FrontendUser $frontendUser */
                    $frontendUser = $process->getFrontendUser();
                }

                if ($process instanceof \Rkw\RkwEvents\Domain\Model\EventReservation) {
                    /** @var \RKW\RkwRegistration\Domain\Model\FrontendUser $frontendUser */
                    $frontendUser = $process->getFeUser();
                }

                if (!$frontendUser) {
                    throw new Exception('surveyRequestManager.error.noFrontendUser');
                }

                $surveyRequest->setFrontendUser($frontendUser);
                $surveyRequest->setProcess($this->markerReducer->implodeMarker(['process' => $process]));

                $process->getTargetGroup()->rewind();
                $surveyRequest->addTargetGroup($process->getTargetGroup()->current());

                $this->surveyRequestRepository->add($surveyRequest);
                $this->persistenceManager->persistAll();

                $this->logDebug(
                    sprintf(
                        'Created surveyRequest for process with uid=%s of type=%s by frontenduser with uid=%s',
                        $process->getUid(),
                        get_class($process),
                        $frontendUser->getUid()
                    )
                );

                return $surveyRequest;

            }
        } catch (\Exception $e) {
        }

        $this->logInfo(
            sprintf(
                'No surveyRequest has been created for process with uid=%s.',
                $process->getUid()
            )
        );

        return null;
    }


    /**
     * @param \TYPO3\CMS\Extbase\DomainObject\AbstractEntity $process
     * @return bool
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     */
    protected function isSurveyable(AbstractEntity $process): bool
    {
        return count($this->getNotifiableObjects($process)) > 0;
    }


}
