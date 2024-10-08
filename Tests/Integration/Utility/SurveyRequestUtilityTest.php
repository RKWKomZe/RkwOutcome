<?php
namespace RKW\RkwOutcome\Tests\Integration\SurveyRequest;

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

use Madj2k\Accelerator\Persistence\MarkerReducer;
use RKW\RkwEvents\Domain\Repository\EventReservationRepository;
use RKW\RkwOutcome\Domain\Model\SurveyRequest;
use RKW\RkwOutcome\Domain\Repository\SurveyConfigurationRepository;
use RKW\RkwOutcome\Domain\Repository\SurveyRequestRepository;
use RKW\RkwOutcome\SurveyRequest\SurveyRequestProcessor;
use RKW\RkwOutcome\Utility\SurveyRequestUtility;
use RKW\RkwShop\Domain\Repository\OrderRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * SurveyRequestUtilityTest
 *
 * @author Christian Dilger <c.dilger@addorange.de>
 * @copyright Rkw Kompetenzzentrum
 * @package RKW_RkwOutcome
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class SurveyRequestUtilityTest extends FunctionalTestCase
{
    /**
     * @const
     */
    const FIXTURE_PATH = __DIR__ . '/SurveyRequestUtilityTest/Fixtures';


    /**
     * @var string[]
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/ajax_api',
        'typo3conf/ext/accelerator',
        'typo3conf/ext/persisted_sanitized_routing',
        'typo3conf/ext/core_extended',
        'typo3conf/ext/fe_register',
        'typo3conf/ext/postmaster',
        'typo3conf/ext/rkw_events',
        'typo3conf/ext/rkw_outcome',
        'typo3conf/ext/rkw_shop',
        'typo3conf/ext/rkw_survey',
        'typo3conf/ext/static_info_tables',
    ];



    /**
     * @var string[]
     */
    protected $coreExtensionsToLoad = [ ];


    /**
     * @var \RKW\RkwEvents\Domain\Repository\EventReservationRepository|null
     */
    private ?EventReservationRepository $eventReservationRepository = null;


    /**
     * @var \RKW\RkwShop\Domain\Repository\OrderRepository|null
     */
    private ?OrderRepository $orderRepository = null;


    /**
     * @var \RKW\RkwOutcome\Domain\Repository\SurveyRequestRepository|null
     */
    private ?SurveyRequestRepository $surveyRequestRepository = null;


    /**
     * @var \RKW\RkwOutcome\Domain\Repository\SurveyConfigurationRepository|null
     */
    private ?SurveyConfigurationRepository $surveyConfigurationRepository = null;


    /**
     * @var \RKW\RkwOutcome\SurveyRequest\SurveyRequestProcessor|null
     */
    private ?SurveyRequestProcessor $surveyRequestProcessor = null;


    /**
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager|null
     */
    protected ?PersistenceManager $persistenceManager = null;


    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager|null
     */
    private ?ObjectManager $objectManager = null;


    /**
     * @var int
     */
    protected int $checkPeriod = 0;


    /**
     * @var int
     */
    protected int $maxSurveysPerPeriodAndFrontendUser = 0;


    /**
     * Setup
     *
     * @return void
     * @throws \Exception
     */
    protected function setUp():void
    {
        parent::setUp();

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Global.xml');
        $this->setUpFrontendRootPage(
            1,
            [
                'EXT:core_extended/Configuration/TypoScript/constants.typoscript',
                'EXT:core_extended/Configuration/TypoScript/setup.typoscript',
                'EXT:accelerator/Configuration/TypoScript/constants.typoscript',
                'EXT:accelerator/Configuration/TypoScript/setup.typoscript',
                'EXT:fe_register/Configuration/TypoScript/constants.typoscript',
                'EXT:fe_register/Configuration/TypoScript/setup.typoscript',
                'EXT:postmaster/Configuration/TypoScript/constants.typoscript',
                'EXT:postmaster/Configuration/TypoScript/setup.typoscript',
                'EXT:rkw_outcome/Configuration/TypoScript/constants.typoscript',
                'EXT:rkw_outcome/Configuration/TypoScript/setup.typoscript',
                'EXT:rkw_events/Configuration/TypoScript/constants.typoscript',
                'EXT:rkw_events/Configuration/TypoScript/setup.typoscript',
                'EXT:rkw_shop/Configuration/TypoScript/constants.typoscript',
                'EXT:rkw_shop/Configuration/TypoScript/setup.typoscript',
                'EXT:rkw_survey/Configuration/TypoScript/constants.typoscript',
                'EXT:rkw_survey/Configuration/TypoScript/setup.typoscript',
                self::FIXTURE_PATH . '/Frontend/Configuration/Rootpage.typoscript',
            ]
        );

        /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);

        /** @var \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager $persistenceManager */
        $this->persistenceManager = GeneralUtility::makeInstance(PersistenceManager::class);

        /** @var \RKW\RkwOutcome\SurveyRequest\SurveyRequestProcessor $surveyRequestProcessor */
        $this->surveyRequestProcessor = $this->objectManager->get(SurveyRequestProcessor::class);

        /** @var \RKW\RkwShop\Domain\Repository\OrderRepository $orderRepository */
        $this->orderRepository = $this->objectManager->get(OrderRepository::class);

        /** @var \RKW\RkwOutcome\Domain\Repository\SurveyConfigurationRepository $surveyConfigurationRepository */
        $this->surveyConfigurationRepository = $this->objectManager->get(SurveyConfigurationRepository::class);

        /** @var \RKW\RkwOutcome\Domain\Repository\SurveyRequestRepository $surveyRequestRepository */
        $this->surveyRequestRepository = $this->objectManager->get(SurveyRequestRepository::class);

        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName'] = 'RKW';
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'] = 'service@mein.rkw.de';
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailReplyName'] = 'RKW';
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailReplyToAddress'] = 'reply@mein.rkw.de';
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailReturnAddress'] = 'bounces@mein.rkw.de';

        $this->checkPeriod = 7 * 24 * 60 * 60;
        $this->maxSurveysPerPeriodAndFrontendUser = 1;
    }

    //==================================================================================================

    /**
     * @param string $model
     * @param int $modelUid
     * @param int $surveyConfigurationUid
     * @return void
     * @throws \Exception
     */
    protected function setUpSurveyRequest(string $model, int $modelUid = 1, int $surveyConfigurationUid = 1): void
    {
        /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $markerReducer = $objectManager->get(MarkerReducer::class);

        /** @var \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequest */
        $surveyRequest = GeneralUtility::makeInstance(SurveyRequest::class);

        $frontendUser = null;

        if ($model === \RKW\RkwShop\Domain\Model\Order::class) {
            $process = $this->orderRepository->findByUid($modelUid);
            $process->getOrderItem()->rewind();
            $processSubject = $process->getOrderItem()->current()->getProduct();
            $frontendUser = $process->getFrontendUser();
        }

        if ($model === \RKW\RkwEvents\Domain\Model\EventReservation::class) {
            $process = $this->eventReservationRepository->findByUid($modelUid);
            $processSubject = $process->getEvent();
            $frontendUser = $process->getFeUser();
        }

        /** @var \RKW\RkwOutcome\Domain\Model\SurveyConfiguration $surveyConfiguration */
        $surveyConfiguration = $this->surveyConfigurationRepository->findByUid($surveyConfigurationUid);
        $surveyRequest->setSurveyConfiguration($surveyConfiguration);
        $surveyRequest->setFrontendUser($frontendUser);

        $process->getTargetGroup()->rewind();
        $surveyRequest->addTargetGroup($process->getTargetGroup()->current());

        $surveyRequest->setProcess($markerReducer->implode(['process' => $process]));
        $surveyRequest->setProcessSubject($markerReducer->implode(['processSubject' => $processSubject]));

        $this->surveyRequestRepository->add($surveyRequest);
        $this->persistenceManager->persistAll();
    }

    //==================================================================================================

    /**
     * @test
     * @throws \Exception
     */
    public function buildSurveyRequestTagsReturnsExpectedResult(): void
    {
        /**
         * Scenario:
         *
         * Given persisted surveyRequest-object
         * When the method is called
         * Then ii should return a comma separated string contain targetGroupUid, class of product and productUid
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check10.xml');

        /** @var \RKW\RkwShop\Domain\Model\Order $order */
        $order = $this->orderRepository->findByUid(1);
        $order->setShippedTstamp(strtotime('-2 days'));
        $this->orderRepository->update($order);

        $this->setUpSurveyRequest(\RKW\RkwShop\Domain\Model\Order::class);

        $this->surveyRequestProcessor->processPendingSurveyRequests(
            $this->checkPeriod,
            $this->maxSurveysPerPeriodAndFrontendUser
        );

        /** @var \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequestDb */
        $surveyRequestDb = $this->surveyRequestRepository->findByUid(1);

        /** @var string $tags */
        $tags = SurveyRequestUtility::buildSurveyRequestTags($surveyRequestDb);
        self::assertEquals('10,Product,2', $tags);
    }

    //==================================================================================================


    /**
     * TearDown
     */
    protected function tearDown(): void
    {
        parent::tearDown();
    }

}

