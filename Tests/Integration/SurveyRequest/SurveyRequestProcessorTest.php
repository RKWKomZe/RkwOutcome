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

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Madj2k\Accelerator\Persistence\MarkerReducer;
use Madj2k\FeRegister\Domain\Repository\FrontendUserRepository;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use RKW\RkwEvents\Domain\Repository\EventRepository;
use RKW\RkwEvents\Domain\Repository\EventReservationRepository;
use RKW\RkwOutcome\Domain\Model\SurveyRequest;
use RKW\RkwOutcome\Domain\Repository\SurveyConfigurationRepository;
use RKW\RkwOutcome\Domain\Repository\SurveyRequestRepository;
use RKW\RkwOutcome\SurveyRequest\SurveyRequestProcessor;
use RKW\RkwShop\Domain\Repository\OrderRepository;
use RKW\RkwSurvey\Domain\Repository\SurveyRepository;
use RKW\RkwSurvey\Domain\Repository\TokenRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

/**
 * SurveyRequestProcessorTest
 *
 * @author Christian Dilger <c.dilger@addorange.de>
 * @copyright Rkw Kompetenzzentrum
 * @package RKW_RkwOutcome
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class SurveyRequestProcessorTest extends FunctionalTestCase
{
    /**
     * @const
     */
    const FIXTURE_PATH = __DIR__ . '/SurveyRequestProcessorTest/Fixtures';


    /**
     * @var string[]
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/accelerator',
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
    protected $coreExtensionsToLoad = [];


    /**
     * @var \Madj2k\FeRegister\Domain\Repository\FrontendUserRepository|null
     */
    private ?FrontendUserRepository $frontendUserRepository = null;


    /**
     * @var \RKW\RkwEvents\Domain\Repository\EventReservationRepository|null
     */
    private ?EventReservationRepository $eventReservationRepository = null;


    /**
     * @var \RKW\RkwShop\Domain\Repository\OrderRepository|null
     */
    private ?OrderRepository $orderRepository = null;


    /**
     * @var \RKW\RkwEvents\Domain\Repository\EventRepository|null
     */
    private ?EventRepository $eventRepository = null;


    /**
     * @var \RKW\RkwOutcome\Domain\Repository\SurveyRequestRepository|null
     */
    private ?SurveyRequestRepository $surveyRequestRepository = null;


    /**
     * @var \RKW\RkwOutcome\Domain\Repository\SurveyConfigurationRepository|null
     */
    private ?SurveyConfigurationRepository $surveyConfigurationRepository = null;


    /**
     * @var \RKW\RkwSurvey\Domain\Repository\SurveyRepository|null
     */
    private ?SurveyRepository $surveyRepository = null;


    /**
     * @var \RKW\RkwSurvey\Domain\Repository\TokenRepository|null
     */
    private ?TokenRepository $tokenRepository = null;


    /**
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager|null
     */
    protected ?PersistenceManager $persistenceManager = null;


    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager|null
     */
    private ?ObjectManager $objectManager = null;


    /**
     * @var \Madj2k\Accelerator\Persistence\MarkerReducer|null
     */
    private ?MarkerReducer $markerReducer = null;


    /**
     * @var \RKW\RkwOutcome\SurveyRequest\SurveyRequestProcessor|null
     */
    private ?SurveyRequestProcessor $fixture = null;


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

        /** @var \RKW\RkwOutcome\SurveyRequest\SurveyRequestProcessor $fixture */
        $this->fixture = $this->objectManager->get(SurveyRequestProcessor::class);

        /** @var \RKW\RkwEvents\Domain\Repository\EventRepository $eventRepository */
        $this->eventRepository = $this->objectManager->get(EventRepository::class);

        /** @var \RKW\RkwEvents\Domain\Repository\EventReservationRepository $eventReservationRepository */
        $this->eventReservationRepository = $this->objectManager->get(EventReservationRepository::class);

        /** @var \Madj2k\FeRegister\Domain\Repository\FrontendUserRepository $frontendUserRepository */
        $this->frontendUserRepository = $this->objectManager->get(FrontendUserRepository::class);

        /** @var \RKW\RkwShop\Domain\Repository\OrderRepository $orderRepository */
        $this->orderRepository = $this->objectManager->get(OrderRepository::class);

        /** @var \RKW\RkwOutcome\Domain\Repository\SurveyConfigurationRepository $surveyConfigurationRepository */
        $this->surveyConfigurationRepository = $this->objectManager->get(SurveyConfigurationRepository::class);

        /** @var \RKW\RkwSurvey\Domain\Repository\SurveyRepository $surveyRepository */
        $this->surveyRepository = $this->objectManager->get(SurveyRepository::class);

        /** @var \RKW\RkwOutcome\Domain\Repository\SurveyRequestRepository $surveyRequestRepository */
        $this->surveyRequestRepository = $this->objectManager->get(SurveyRequestRepository::class);

        /** @var \RKW\RkwSurvey\Domain\Repository\TokenRepository $tokenRepository */
        $this->tokenRepository = $this->objectManager->get(TokenRepository::class);

        /** @var \Madj2k\Accelerator\Persistence\MarkerReducer $markerReducer */
        $this->markerReducer = $this->objectManager->get(MarkerReducer::class);

        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName'] = 'RKW';
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'] = 'service@mein.rkw.de';
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailReplyName'] = 'RKW';
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailReplyToAddress'] = 'reply@mein.rkw.de';
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailReturnAddress'] = 'bounces@mein.rkw.de';

        $this->checkPeriod = 7 * 24 * 60 * 60;
        $this->maxSurveysPerPeriodAndFrontendUser = 1;

    }


    #==============================================================================

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
    public function processPendingSurveyRequestMarksAsNotifiedIfShippedTstampIsLessThanSurveyWaitingTime(): void
    {
        /**
         * Scenario:
         *
         * Given a persisted product
         * Given a persisted surveyConfiguration-object
         * Given the surveyConfiguration-property surveyWaitingTime is set to 1 * 24 * 60 * 60 seconds (1 day)
         * Given the surveyConfiguration-property survey is set to that survey-object
         * Given the surveyConfiguration-property product is set to that product-object
         * Given the targetGroup-object 1 is attached to that surveyConfiguration-object
         * Given a persisted order-object
         * Given the order-property shippedTstamp is set to less than (now (time()) - surveyWaitingTime (1 day))
         * Given the product-property ot the contained orderItem-object is set to the same product-object
         * Given the targetGroup-object 1 is attached to that order-object
         * Given a persisted surveyRequest-object
         * Given that surveyRequest-object belongs to a persisted frontendUser
         * Given that frontendUser as opted-in to marketing
         * Given the surveyRequest-property process is set to that order-object
         * Given the surveyRequest-property processSubject is set to that product-object
         * Given the surveyRequest-property surveyConfiguration is set to the persisted surveyConfiguration-object
         * When the method is called
         * Then the surveyRequest-property notifiedTstamp is set to > 0
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check10.xml');

        /** @var \RKW\RkwShop\Domain\Model\Order $order */
        $order = $this->orderRepository->findByUid(1);
        $order->setShippedTstamp(strtotime('-2 days'));
        $this->orderRepository->update($order);

        $this->setUpSurveyRequest(\RKW\RkwShop\Domain\Model\Order::class, 1, 1);

        $notifiedSurveyRequests = $this->fixture->processPendingSurveyRequests(
            $this->checkPeriod,
            $this->maxSurveysPerPeriodAndFrontendUser
        );

        self::assertCount(1, $notifiedSurveyRequests);

        /** @var \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequestDb */
        $surveyRequestDb = $this->surveyRequestRepository->findByUid(1);
        self::assertGreaterThan(0, $surveyRequestDb->getNotifiedTstamp());
    }


    /**
     * @test
     * @throws \Exception
     */
    public function processPendingSurveyRequestDeletesIfFrontendUserHasNoMarketingOptin(): void
    {
        /**
         * Scenario:
         *
         * Given a persisted product
         * Given a persisted surveyConfiguration-object
         * Given the surveyConfiguration-property surveyWaitingTime is set to 1 * 24 * 60 * 60 seconds (1 day)
         * Given the surveyConfiguration-property survey is set to that survey-object
         * Given the surveyConfiguration-property product is set to that product-object
         * Given the targetGroup-object 1 is attached to that surveyConfiguration-object
         * Given a persisted order-object
         * Given the order-property shippedTstamp is set to less than (now (time()) - surveyWaitingTime (1 day))
         * Given the product-property ot the contained orderItem-object is set to the same product-object
         * Given the targetGroup-object 1 is attached to that order-object
         * Given a persisted surveyRequest-object
         * Given that surveyRequest-object belongs to a persisted frontendUser
         * Given that frontendUser as NOT opted-in to marketing
         * Given the surveyRequest-property process is set to that order-object
         * Given the surveyRequest-property processSubject is set to that product-object
         * Given the surveyRequest-property surveyConfiguration is set to the persisted surveyConfiguration-object
         * When the method is called
         * Then no surveyRequests have been sent
         * Then the former surveyRequest has been deleted
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check15.xml');

        /** @var \RKW\RkwShop\Domain\Model\Order $order */
        $order = $this->orderRepository->findByUid(1);
        $order->setShippedTstamp(strtotime('-2 days'));
        $this->orderRepository->update($order);

        $this->setUpSurveyRequest(\RKW\RkwShop\Domain\Model\Order::class, 1, 1);

        $notifiedSurveyRequests = $this->fixture->processPendingSurveyRequests(
            $this->checkPeriod,
            $this->maxSurveysPerPeriodAndFrontendUser
        );

        self::assertCount(0, $notifiedSurveyRequests);

        /** @var  \TYPO3\CMS\Extbase\Persistence\QueryResultInterface $surveyRequests */
        $surveyRequestsDb = $this->surveyRequestRepository->findAll();
        self::assertCount(0, $surveyRequestsDb);
    }


    /**
     * @test
     * @throws \Exception
     */
    public function processPendingSurveyRequestDoesNotMarkAsNotifiedIfShippedTstampIsGreaterThanSurveyWaitingTime(): void
    {
        /**
         * Scenario:
         *
         * Given a persisted product
         * Given a persisted surveyConfiguration-object
         * Given the surveyConfiguration-property survey is set to that survey-object
         * Given the surveyConfiguration-property product is set to that product-object
         * Given the surveyConfiguration-property surveyWaitingTime is set to 2 * 24 * 60 * 60 seconds (2 days)
         * Given the targetGroup-object 1 is attached to that surveyConfiguration-object
         * Given a persisted order-object
         * Given the order-property shippedTstamp is set to greater than (now (time()) - surveyWaitingTime (2 days))
         * Given the product-property ot the contained orderItem-object is set to the same product-object
         * Given the targetGroup-object 1 is attached to that order-object
         * Given a persisted surveyRequest-object
         * Given that surveyRequest-object belongs to a persisted frontendUser
         * Given that frontendUser as opted-in to marketing
         * Given the surveyRequest-property process is set to that order-object
         * When the method is called
         * Then the surveyRequest-property notifiedTstamp remains 0
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check20.xml');

        /** @var \RKW\RkwShop\Domain\Model\Order $order */
        $order = $this->orderRepository->findByUid(1);
        $order->setShippedTstamp(strtotime('-1 day'));
        $this->orderRepository->update($order);
        $this->persistenceManager->persistAll();

        $this->setUpSurveyRequest(\RKW\RkwShop\Domain\Model\Order::class);
        /** @var \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequestDbSetup */
        $surveyRequestDbSetup = $this->surveyRequestRepository->findByUid(1);

        /** @var \RKW\RkwOutcome\Domain\Model\SurveyConfiguration $surveyConfiguration */
        $surveyConfiguration = $this->surveyConfigurationRepository->findByUid(1);
        $surveyRequestDbSetup->setSurveyConfiguration($surveyConfiguration);

        $this->assertEquals(2 * 24 * 60 * 60, $surveyConfiguration->getSurveyWaitingTime());

        $notifiedSurveyRequests = $this->fixture->processPendingSurveyRequests(
            $this->checkPeriod,
            $this->maxSurveysPerPeriodAndFrontendUser
        );
        self::assertCount(0, $notifiedSurveyRequests);

        /** @var \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequestDbProcessed */
        $surveyRequestDbProcessed = $this->surveyRequestRepository->findByUid(1);
        self::assertSame(0, $surveyRequestDbProcessed->getNotifiedTstamp());
    }


    /**
     * @test
     * @throws \Exception
     */
    public function processPendingSurveyRequestSetsProcessedSubjectToCorrectProduct(): void
    {
        /**
         * Scenario:
         *
         * Given a persisted product
         * Given a persisted surveyConfiguration-object
         * Given the surveyConfiguration-property is set to that product-object
         * Given the surveyConfiguration-property surveyWaitingTime set to 1 * 24 * 60 * 60 (1 day)
         * Given a second persisted product
         * Given a persisted order-object
         * Given the order-object contains two orderItem-objects
         * Given the product-property of the first orderItem-object is set to the first product-object
         * Given the product-property of the second orderItem-object is set to the second product-object
         * Given a persisted surveyRequest-object
         * Given that surveyRequest-object belongs to a persisted frontendUser
         * Given that frontendUser as opted-in to marketing
         * Given the surveyRequest-property process is set to that order
         * When the method is called
         * Then the surveyRequest-property notifiedTstamp is set to > 0
         * Then the surveyRequest-property processSubject is set to the first product-object set in first orderItem-object
         * Then the surveyRequest-property processSubject is not set to the second product-object set in second orderItem-object
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check30.xml');

        //  dynamically set shippedTstamp to be less than time() - $surveyWaitingTime
        /** @var \RKW\RkwShop\Domain\Model\Order $order */
        $order = $this->orderRepository->findByUid(1);
        $order->setShippedTstamp(strtotime('-2 days'));
        $this->orderRepository->update($order);

        $this->setUpSurveyRequest(\RKW\RkwShop\Domain\Model\Order::class);

        $notifiedSurveyRequests = $this->fixture->processPendingSurveyRequests(
            $this->checkPeriod,
            $this->maxSurveysPerPeriodAndFrontendUser
        );
        self::assertCount(1, $notifiedSurveyRequests);

        /** @var \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequestDb */
        $surveyRequestDb = $this->surveyRequestRepository->findByUid(1);
        self::assertGreaterThan(0, $surveyRequestDb->getNotifiedTstamp());

        $processMarker = $this->markerReducer->explode($surveyRequestDb->getProcess());
        self::assertSame($order, $processMarker['process']);

        $processSubjectMarker = $this->markerReducer->explode($surveyRequestDb->getProcessSubject());
        self::assertEquals(1, $processSubjectMarker['processSubject']->getUid());
        self::assertNotEquals(2, $processSubjectMarker['processSubject']->getUid());
    }


    /**
     * @test
     * @todo Move to SurveyRequestCreatorTest
     * @throws \Exception
     */
    public function processPendingSurveyRequestSetsProcessSubjectToRandomProduct(): void
    {
        /**
         * Scenario:
         *
         * Given a persisted surveyRequest-object
         * Given that survey belongs to a persisted frontendUser
         * Given that frontendUser as opted-in to marketing
         * Given a first persisted product
         * Given that survey belongs to a persisted frontendUser
         * Given that frontendUser as opted-in to marketing
         * Given a first persisted surveyConfiguration-object
         * Given the surveyConfiguration-property of the first surveyConfiguration-object is set to the first product-object
         * Given a second persisted product
         * Given a second persisted surveyConfiguration-object
         * Given the surveyConfiguration-property of the second surveyConfiguration-object is set to the second product-object
         * Given a third persisted product
         * Given the third product-object is not associated with any surveyConfiguration-object
         * Given a persisted order-object
         * Given the order-object contains two orderItem-objects
         * Given the product-property of the first orderItem-object is set to the first product-object
         * Given the product-property of the second orderItem-object is set to the second product-object
         * Given the product-property of the third orderItem-object is set to the third product-object
         * Given a persisted surveyRequest-object
         * Given that surveyRequest-object belongs to a persisted frontendUser
         * Given that frontendUser as opted-in to marketing
         * Given the surveyRequest-property process is set to that order
         * When the method is called
         * Then the surveyRequest-property notifiedTstamp is set to > 0
         * Then the surveyRequest-property processSubject is not set to the third product-object set in third orderItem-object
         * Then the surveyRequest-property processSubject is set either to the first product-object set first orderItem-object
         * Or to the second product-object set second orderItem-object
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check40.xml');

        //  dynamically set shippedTstamp to be less than time() - $surveyWaitingTime
        /** @var \RKW\RkwShop\Domain\Model\Order $order */
        $order = $this->orderRepository->findByUid(1);
        $order->setShippedTstamp(strtotime('-2 days'));
        $this->orderRepository->update($order);

        $this->setUpSurveyRequest(\RKW\RkwShop\Domain\Model\Order::class);

        $notifiedSurveyRequests = $this->fixture->processPendingSurveyRequests(
            $this->checkPeriod,
            $this->maxSurveysPerPeriodAndFrontendUser
        );
        self::assertCount(1, $notifiedSurveyRequests);

        /** @var \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequestDb */
        $surveyRequestDb = $this->surveyRequestRepository->findByUid(1);
        self::assertGreaterThan(0, $surveyRequestDb->getNotifiedTstamp());

        $processMarker = $this->markerReducer->explode($surveyRequestDb->getProcess());
        self::assertSame($order, $processMarker['process']);

        $processSubjectMarker = $this->markerReducer->explode($surveyRequestDb->getProcessSubject());
        self::assertNotEquals(3, $processSubjectMarker['processSubject']->getUid());
        self::assertContains($processSubjectMarker['processSubject']->getUid(), [1,2]);
    }


    /**
     * @test
     * @todo Move to SurveyRequestCreatorTest
     * @throws \Exception
     */
    public function processPendingSurveyRequestSetsProcessSubjectToSingleProduct(): void
    {
        /**
         * Scenario:
         *
         * Given a persisted targetGroup 1
         * Given a persisted targetGroup 2
         * Given a persisted product 1
         * Given a persisted surveyConfiguration-object 1
         * Given the surveyConfiguration-property is set to that product-object 1
         * Given the targetGroup 1 is attached to surveyConfiguration 1
         * Given a second persisted product 2
         * Given a persisted surveyConfiguration-object 2
         * Given the surveyConfiguration-property is set to that product-object 2
         * Given the targetGroup 2 is attached to surveyConfiguration 2
         * Given a persisted order-object
         * Given the targetGroup 1 is attached to the order-object
         * Given the order-object contains two orderItem-objects
         * Given the product-property of the first orderItem-object is set to the product-object 1
         * Given the product-property of the second orderItem-object is set to the product-object 2
         * Given a persisted surveyRequest-object
         * Given that surveyRequest-object belongs to a persisted frontendUser
         * Given that frontendUser as opted-in to marketing
         * Given the surveyRequest-property process is set to that order
         * When the method is called
         * Then the surveyRequest-property notifiedTstamp is set to > 0
         * Then the surveyRequest-property processSubject is set to the product-object 1
         * Then the surveyRequest-property processSubject is not set to the product-object 2
         * Then the surveyRequest-property surveyConfiguration is set to the surveyConfiguration-object 1
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check50.xml');

        //  dynamically set shippedTstamp to be less than time() - $surveyWaitingTime
        /** @var \RKW\RkwShop\Domain\Model\Order $order */
        $order = $this->orderRepository->findByUid(1);
        $order->setShippedTstamp(strtotime('-2 days'));
        $this->orderRepository->update($order);

        $this->setUpSurveyRequest(\RKW\RkwShop\Domain\Model\Order::class);

        $notifiedSurveyRequests = $this->fixture->processPendingSurveyRequests(
            $this->checkPeriod,
            $this->maxSurveysPerPeriodAndFrontendUser
        );
        self::assertCount(1, $notifiedSurveyRequests);

        /** @var \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequestDb */
        $surveyRequestDb = $this->surveyRequestRepository->findByUid(1);
        self::assertGreaterThan(0, $surveyRequestDb->getNotifiedTstamp());

        $processMarker = $this->markerReducer->explode($surveyRequestDb->getProcess());
        self::assertSame($order, $processMarker['process']);

        $processSubjectMarker = $this->markerReducer->explode($surveyRequestDb->getProcessSubject());
        self::assertSame(1, $processSubjectMarker['processSubject']->getUid());
        self::assertNotSame(2, $processSubjectMarker['processSubject']->getUid());

        self::assertSame(1, $surveyRequestDb->getSurveyConfiguration()->getUid());
    }


    /**
     * @test
     * @throws \Exception
     */
    public function processPendingSurveyRequestIgnoresAlreadyProcessedSurveyRequest(): void
    {
        /**
         * Scenario:
         *
         * Given a persisted surveyRequest-object 1
         * Given that surveyRequest-object 1 belongs to a persisted frontendUser
         * Given that frontendUser as opted-in to marketing
         * Given property notifiedTstamp of surveyRequest-object 1 is set to 1
         * Given a persisted surveyRequest-object 2
         * Given that surveyRequest-object 2 belongs to a persisted frontendUser
         * Given that frontendUser as opted-in to marketing
         * When the method is called
         * Then the number of processed requests is 1
         * Then the property notifiedTstamp of surveyRequest-object 1 remains 1
         * Then the property notifiedTstamp of surveyRequest-object 2 is greater than 1
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check60.xml');

        //  dynamically set shippedTstamp to be less than time() - $surveyWaitingTime
        /** @var \RKW\RkwShop\Domain\Model\Order $order */
        $order = $this->orderRepository->findByUid(1);
        $order->setShippedTstamp(strtotime('-10 days'));
        $this->orderRepository->update($order);

        $order = $this->orderRepository->findByUid(2);
        $order->setShippedTstamp(strtotime('-2 days'));
        $this->orderRepository->update($order);

        $this->setUpSurveyRequest(\RKW\RkwShop\Domain\Model\Order::class);
        $this->setUpSurveyRequest(\RKW\RkwShop\Domain\Model\Order::class, 2, 2);

        /** @var \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequestAlreadyNotifiedDb */
        $surveyRequestAlreadyNotifiedDb = $this->surveyRequestRepository->findByUid(1);
        $surveyRequestAlreadyNotifiedDb->setNotifiedTstamp(1);
        $this->surveyRequestRepository->update($surveyRequestAlreadyNotifiedDb);
        $this->persistenceManager->persistAll();

        $notifiedSurveyRequests = $this->fixture->processPendingSurveyRequests(
            $this->checkPeriod,
            $this->maxSurveysPerPeriodAndFrontendUser
        );

        self::assertCount(1, $notifiedSurveyRequests);

        /** @var \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequestProcessedDb1 */
        $surveyRequestProcessedDb1 = $this->surveyRequestRepository->findByUid(1);
        self::assertEquals(1, $surveyRequestProcessedDb1->getNotifiedTstamp());

        /** @var \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequestProcessedDb2 */
        $surveyRequestProcessedDb2 = $this->surveyRequestRepository->findByUid(2);
        self::assertGreaterThan(1, $surveyRequestProcessedDb2->getNotifiedTstamp());
    }


    /**
     * @test
     * @throws \Exception
     */
    public function processPendingSurveyRequestsRespectsSeparateFrontendUsers(): void
    {
        /**
         * Scenario:
         *
         * Given a persisted frontendUser 1
         * Given that frontendUser 1 as opted-in to marketing
         * Given a persisted surveyRequest-object 1 that belongs to frontendUser-object 1
         * Given a persisted order-object 1 that belongs to surveyRequest-object 1
         * Given a persisted product-object 1 that belongs to order-object 1
         * Given a persisted frontendUser 2
         * Given that frontendUser 2 as opted-in to marketing
         * Given a persisted surveyRequest-object 2 that belongs to frontendUser-object 2
         * Given a persisted order-object 2 that belongs to surveyRequest-object 2
         * Given a persisted product-object 2 that belongs to order-object 2
         * When the method is called
         * Then the number of processed requests is 2
         * Then the property notifiedTstamp of both surveyRequest-objects is greater than 1
         * Then the property processedSubject of both surveyRequest-objects is set
         * Then the property processSubject of surveyRequest-object 1 is set to product-object 1
         * Then the property processSubject of surveyRequest-object 2 is set to product-object 2
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check80.xml');

        //  dynamically set shippedTstamp to be less than time() - $surveyWaitingTime
        /** @var \RKW\RkwShop\Domain\Model\Order $order */
        $order = $this->orderRepository->findByUid(1);
        $order->setShippedTstamp(strtotime('-2 days'));
        $this->orderRepository->update($order);

        $order = $this->orderRepository->findByUid(2);
        $order->setShippedTstamp(strtotime('-2 days'));
        $this->orderRepository->update($order);

        $this->setUpSurveyRequest(\RKW\RkwShop\Domain\Model\Order::class);
        $this->setUpSurveyRequest(\RKW\RkwShop\Domain\Model\Order::class, 2, 2);

        $notifiedSurveyRequests = $this->fixture->processPendingSurveyRequests(
            $this->checkPeriod,
            $this->maxSurveysPerPeriodAndFrontendUser
        );
        self::assertCount(2, $notifiedSurveyRequests);

        /** @var \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequestProcessedDbUid1 */
        $surveyRequestProcessedDbUid1 = $this->surveyRequestRepository->findByUid(1);
        self::assertGreaterThan(0, $surveyRequestProcessedDbUid1->getNotifiedTstamp());

        $processSubjectMarker1 = $this->markerReducer->explode($surveyRequestProcessedDbUid1->getProcessSubject());
        self::assertEquals(1, $processSubjectMarker1['processSubject']->getUid());

        /** @var \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequestProcessedDbUid2 */
        $surveyRequestProcessedDbUid2 = $this->surveyRequestRepository->findByUid(2);
        self::assertGreaterThan(0, $surveyRequestProcessedDbUid2->getNotifiedTstamp());

        $processSubjectMarker2 = $this->markerReducer->explode($surveyRequestProcessedDbUid2->getProcessSubject());
        self::assertEquals(2, $processSubjectMarker2['processSubject']->getUid());
    }


    /**
     * @test
     * @throws \Exception
     */
    public function processPendingSurveyRequestsRespectsSurveyTimeSlotAndSurveyPerTimeSlotAndFrontendUser(): void
    {
        /**
         * Scenario:
         *
         * Given checkPeriod is set to 7 * 24 * 60 * 60 (last 7 days)
         * Given maxSurveysPerPeriodAndFrontendUser is set to 1
         * Given surveyWaitingTime is set to 86400
         * Given a persisted frontendUser 1
         * Given that frontendUser 1 has opted-in to marketing
         * Given a persisted surveyRequest-object 1 that belongs to frontendUser-object 1
         * Given a persisted surveyRequest-object 2 that belongs to frontendUser-object 1
         * When the method is called 2 days (surveyWaitingTime + 1 day) after the order
         * Then the number of processed requests is 1 (selected out of 2 pending survey requests)
         * Given an additional persisted surveyRequest-object 3 that belongs to frontendUser-object 1
         * When the method is called again less than 7 days (checkPeriod) after its first call
         * Then the number of processed requests is 0
         * When the method is called again later than 7 days (checkPeriod) after its first call
         * Then the number of processed requests is 1
         * Then the number of pending requests is 0
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check90.xml');

        //  set now to initial shipping date = first workday of the year
        $initialDate = CarbonImmutable::now()->startOfYear();
        Carbon::setTestNow($initialDate);

        //  First two orders shipped on $initialDate
        /** @var \RKW\RkwShop\Domain\Model\Order $order1 */
        $order1 = $this->orderRepository->findByUid(1);
        $order1->setShippedTstamp($initialDate->timestamp);
        $this->orderRepository->update($order1);

        /** @var \RKW\RkwShop\Domain\Model\Order $order2 */
        $order2 = $this->orderRepository->findByUid(2);
        $order2->setShippedTstamp($initialDate->timestamp);
        $this->orderRepository->update($order2);

        $this->setUpSurveyRequest(\RKW\RkwShop\Domain\Model\Order::class);
        $this->setUpSurveyRequest(\RKW\RkwShop\Domain\Model\Order::class, 2);

        //  Processing pending survey requests on $initialDate + 2 days
        $processedSurveyRequests = $this->fixture->processPendingSurveyRequests(
            $this->checkPeriod,
            $this->maxSurveysPerPeriodAndFrontendUser,
            $initialDate->addDays(2)->timestamp
        );
        self::assertCount(1, $processedSurveyRequests);

        /** @var  \TYPO3\CMS\Extbase\Persistence\QueryResultInterface $pendingSurveyRequestsDb */
        $pendingSurveyRequestsDb = $this->surveyRequestRepository->findPendingSurveyRequests();
        self::assertCount(0, $pendingSurveyRequestsDb);

        //  Third order on $initialDate + 2 days
        /** @var \RKW\RkwShop\Domain\Model\Order $order */
        $order3 = $this->orderRepository->findByUid(3);
        $order3->setShippedTstamp($initialDate->addDays(2)->timestamp);
        $this->orderRepository->update($order3);

        $this->setUpSurveyRequest(\RKW\RkwShop\Domain\Model\Order::class, 3);

        //  Processing pending survey requests on $initialDate + 4 days
        $processedSurveyRequests = $this->fixture->processPendingSurveyRequests(
            $this->checkPeriod,
            $this->maxSurveysPerPeriodAndFrontendUser,
            $initialDate->addDays(4)->timestamp
        );
        self::assertCount(0, $processedSurveyRequests);

        /** @var  \TYPO3\CMS\Extbase\Persistence\QueryResultInterface $pendingSurveyRequestsDb */
        $pendingSurveyRequestsDb = $this->surveyRequestRepository->findPendingSurveyRequests();

        self::assertCount(1, $pendingSurveyRequestsDb);

        //  Processing pending survey requests on $initialDate + $checkPeriod + 1 day
        $processedSurveyRequests = $this->fixture->processPendingSurveyRequests(
            $this->checkPeriod,
            $this->maxSurveysPerPeriodAndFrontendUser,
            $initialDate->addDays(9)->timestamp
        );
        self::assertCount(1, $processedSurveyRequests);
        self::assertEquals(3, $processedSurveyRequests[0]->getUid());

        /** @var  \TYPO3\CMS\Extbase\Persistence\QueryResultInterface $pendingSurveyRequestsDb */
        $pendingSurveyRequestsDb = $this->surveyRequestRepository->findPendingSurveyRequests();

        self::assertCount(0, $pendingSurveyRequestsDb);

    }

    //==================================================================================================

    /**
     * @test
     * @throws \Exception
     */
    public function generateTokensAddsTokenToAccessRestrictedSurvey(): void
    {
        /**
         * Scenario:
         *
         * Given a persisted survey-object 1
         * Given the property accessRestricted of survey-object 1 is set to true
         * Given a persisted survey-object 2
         * Given the property accessRestricted of survey-object 1 is set to false
         * Given a persisted product-object
         * Given a persisted surveyConfiguration-object containing both survey-objects and that product-object
         * When the method is called
         * Then a new token is added to survey-object 1
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check100.xml');

        /* @var \RKW\RkwOutcome\Domain\Model\SurveyConfiguration $surveyConfigurationDb */
        $surveyConfigurationDb = $this->surveyConfigurationRepository->findByUid(1);

        $generatedTokens = $this->fixture->generateTokens($surveyConfigurationDb);
        self::assertCount(1, $generatedTokens);
        self::assertTrue(isset($generatedTokens[1]));

        /* @var \RKW\RkwSurvey\Domain\Model\Survey $surveyDb1 */
        $surveyDb1 = $this->surveyRepository->findByUid(1);

        /* @var \RKW\RkwSurvey\Domain\Model\Survey $surveyDb2 */
        $surveyDb2 = $this->surveyRepository->findByUid(2);

        self::assertCount(1, $surveyDb1->getToken());
        self::assertCount(0, $surveyDb2->getToken());

        $tokenList = $this->tokenRepository->findBySurvey($surveyDb1);
        self::assertCount(1, $tokenList->toArray());
        $surveyDb1->getToken()->rewind();
        self::assertSame($tokenList->getFirst()->getName(), $surveyDb1->getToken()->current()->getName());
    }


    /**
     * @test
     * @throws \Exception
     */
    public function processPendingSurveyRequestMarksProcessedSurveyRequestAsNotifiedIfSurveyRequestContainsAnEventAndEndTstampIsLessThanNowMinusSurveyWaitingTime(): void
    {
        /**
         * Scenario:
         *
         * Given the surveyWaitingTime is set to 1 * 24 * 60 * 60 seconds (1 day)
         * Given a persisted survey
         * Given a persisted event
         * Given a persisted surveyConfiguration-object
         * Given the surveyConfiguration-property survey is set to that survey-object
         * Given the surveyConfiguration-property event is set to that event-object
         * Given the targetGroup-object 1 is attached to that surveyConfiguration-object
         * Given a persisted eventReservation-object
         * Given the event-property end is set to greater than (now (time()) - surveyWaitingTime (1 day))
         * Given the event-property ot the eventReservation-object is set to the same event-object
         * Given the targetGroup-object 1 is attached to that eventReservation-object
         * Given a persisted surveyRequest-object
         * Given the surveyRequest-property process is set to that eventReservation-object
         * When the method is called
         * Then the surveyRequest-property notifiedTstamp is set to > 0
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check110.xml');

        /** @var \RKW\RkwEvents\Domain\Model\Event $event */
        $event = $this->eventRepository->findByUid(1);
        $event->setStart(strtotime('-50 hours'));
        $event->setEnd(strtotime('-48 hours'));
        $this->eventRepository->update($event);

        $this->setUpSurveyRequest(\RKW\RkwEvents\Domain\Model\EventReservation::class);

        $notifiedSurveyRequests = $this->fixture->processPendingSurveyRequests(
            $this->checkPeriod,
            $this->maxSurveysPerPeriodAndFrontendUser
        );

        self::assertCount(1, $notifiedSurveyRequests);

        /** @var \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequestDb */
        $surveyRequestDb = $this->surveyRequestRepository->findByUid(1);
        self::assertGreaterThan(0, $surveyRequestDb->getNotifiedTstamp());

        $processMarker = $this->markerReducer->explode($surveyRequestDb->getProcess());
        self::assertInstanceOf(\RKW\RkwEvents\Domain\Model\EventReservation::class, $processMarker['process']);

        $processSubjectMarker = $this->markerReducer->explode($surveyRequestDb->getProcessSubject());
        self::assertInstanceOf(\RKW\RkwEvents\Domain\Model\Event::class, $processSubjectMarker['processSubject']);
        self::assertSame($event, $processSubjectMarker['processSubject']);
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

