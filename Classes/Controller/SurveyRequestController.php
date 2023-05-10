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

use RKW\RkwOutcome\Domain\Repository\SurveyRequestRepository;

/**
 * SurveyRequestController
 *
 * @author Christian Dilger <c.dilger@addorange.de>
 * @copyright Rkw Kompetenzzentrum
 * @package RKW_RkwOutcome
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class SurveyRequestController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{


    /**
     * @var \RKW\RkwOutcome\Domain\Repository\SurveyRequestRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected SurveyRequestRepository $surveyRequestRepository;


    /**
     * action show
     *
     * @return void
     */
    public function showAction()
    {
        /** @var \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequest */
        $surveyRequest = $this->surveyRequestRepository->findByUid(14);
        $this->view->assign('surveyRequest', $surveyRequest);
    }

}
