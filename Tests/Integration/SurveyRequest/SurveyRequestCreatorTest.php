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
use Madj2k\FeRegister\Domain\Repository\FrontendUserRepository;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use RKW\RkwEvents\Domain\Model\EventReservation;
use RKW\RkwEvents\Domain\Repository\EventRepository;
use RKW\RkwEvents\Domain\Repository\EventReservationRepository;
use RKW\RkwOutcome\Domain\Model\SurveyRequest;
use RKW\RkwOutcome\Domain\Repository\SurveyConfigurationRepository;
use RKW\RkwOutcome\Domain\Repository\SurveyRequestRepository;
use RKW\RkwOutcome\SurveyRequest\SurveyRequestCreator;
use RKW\RkwShop\Domain\Repository\OrderRepository;
use RKW\RkwSurvey\Domain\Repository\SurveyRepository;
use RKW\RkwSurvey\Domain\Repository\TokenRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

/**
 * SurveyRequestCreatorTest
 *
 * @author Christian Dilger <c.dilger@addorange.de>
 * @copyright Rkw Kompetenzzentrum
 * @package RKW_RkwOutcome
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class SurveyRequestCreatorTest extends FunctionalTestCase
{
    /**
     * @const
     */
    const FIXTURE_PATH = __DIR__ . '/SurveyRequestCreatorTest/Fixtures';


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
    protected $coreExtensionsToLoad = [ ];


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
    private $surveyRequestRepository = null;


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
    private $markerReducer = null;


    /**
     * @var \RKW\RkwOutcome\SurveyRequest\SurveyRequestCreator|null
     */
    private ?SurveyRequestCreator $fixture = null;


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

        /** @var \RKW\RkwOutcome\SurveyRequest\SurveyRequestCreator $fixture */
        $this->fixture = $this->objectManager->get(SurveyRequestCreator::class);

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

    //==================================================================================================

    /**
     * @test
     * @throws \Exception
     */
    public function createSurveyRequestPersistsNewSurveyRequestIfOrderContainsProductMatchingSurveyConfiguration(): void
    {
        /**
         * Scenario:
         *
         * Given an order-object that is persisted
         * Given a targetGroup-object 1 that is attached to that order-object
         * Given an orderItem-object that belongs to that order-object
         * Given a product-object that belongs to that orderItem-object
         * Given a surveyConfiguration-object that is persisted
         * Given the surveyConfiguration-property product is set to the same product-object as the orderItem-property product
         * Given the targetGroup-object 1 attached to the surveyConfiguration
         * When the method is called
         * Then a new surveyRequest-object is persisted
         * Then the surveyConfiguration-property of this surveyRequest-object is set to the surveyConfiguration object
         * Then the process-property of that surveyRequest-object is set to the order object
         * Then the processSubject-property of that surveyRequest-object is set to the surveyConfiguration-property product
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check10.xml');

        /** @var \RKW\RkwShop\Domain\Model\Order $order */
        $order = $this->orderRepository->findByUid(1);

        /** @var \RKW\RkwOutcome\Domain\Model\SurveyConfiguration $surveyConfigurationDb */
        $surveyConfigurationDb = $this->surveyConfigurationRepository->findByUid(1);

        /** @var  \TYPO3\CMS\Extbase\Persistence\QueryResultInterface $surveyRequests */
        $surveyRequestsDb = $this->surveyRequestRepository->findAll();
        self::assertCount(0, $surveyRequestsDb);

        $this->fixture->createSurveyRequest($order);

        /** @var  \TYPO3\CMS\Extbase\Persistence\QueryResultInterface $surveyRequests */
        $surveyRequestsDb = $this->surveyRequestRepository->findAll();
        self::assertCount(1, $surveyRequestsDb);

        /** @var \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequest */
        $surveyRequestDb = $surveyRequestsDb->getFirst();
        self::assertInstanceOf(SurveyRequest::class, $surveyRequestDb);

        self::assertSame($surveyConfigurationDb, $surveyRequestDb->getSurveyConfiguration());

        $processMarker = $this->markerReducer->explode($surveyRequestDb->getProcess());
        self::assertSame($order, $processMarker['process']);

        $order->getOrderItem()->rewind();
        $processSubjectMarker = $this->markerReducer->explode($surveyRequestDb->getProcessSubject());
        self::assertSame($order->getOrderItem()->current()->getProduct(), $processSubjectMarker['processSubject']);
    }


    /**
     * @test
     * @throws \Exception
     */
    public function createSurveyRequestPersistsNewSurveyRequestWithRandomProcessSubjectIfProductsMatchDifferentSurveyConfigurations(): void
    {
        /**
         * Scenario:
         *
         * Given an order-object that is persisted
         * Given a targetGroup-object 1 that is attached to that order-object
         * Given an orderItem-object 1 that belongs to that order-object
         * Given a product-object 1 that belongs to that orderItem-object 1
         * Given a surveyConfiguration-object 1 that is persisted
         * Given the surveyConfiguration-object 1 property product is set to the product-object 1
         * Given the targetGroup-object 1 is attached to the surveyConfiguration-object 1
         * Given an orderItem-object 2 that belongs to that order-object
         * Given a product-object 2 that belongs to that orderItem-object 2
         * Given a surveyConfiguration-object 2 that is persisted
         * Given the surveyConfiguration-object 2 property product is set to the product-object 2
         * Given the targetGroup-object 1 is attached to the surveyConfiguration-object 2
         * When the method is called
         * Then a new surveyRequest-object is persisted
         * Then the surveyConfiguration-property of this surveyRequest-object is randomly set to one of the surveyConfiguration objects
         * Then the process-property of that surveyRequest-object is set to the order object
         * Then the processSubject-property of that surveyRequest-object is set to the surveyConfiguration-property product of the randomly selected surveyConfiguration object
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check15.xml');

        /** @var \RKW\RkwShop\Domain\Model\Order $order */
        $order = $this->orderRepository->findByUid(1);

        /** @var \RKW\RkwOutcome\Domain\Model\SurveyConfiguration $surveyConfigurationDb */
        $surveyConfigurationDb = $this->surveyConfigurationRepository->findByUid(1);

        /** @var  \TYPO3\CMS\Extbase\Persistence\QueryResultInterface $surveyRequests */
        $surveyRequestsDb = $this->surveyRequestRepository->findAll();
        self::assertCount(0, $surveyRequestsDb);

        $this->fixture->createSurveyRequest($order);

        /** @var  \TYPO3\CMS\Extbase\Persistence\QueryResultInterface $surveyRequests */
        $surveyRequestsDb = $this->surveyRequestRepository->findAll();
        self::assertCount(1, $surveyRequestsDb);

        /** @var \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequest */
        $surveyRequestDb = $surveyRequestsDb->getFirst();
        self::assertInstanceOf(SurveyRequest::class, $surveyRequestDb);

        self::assertContains($surveyRequestDb->getSurveyConfiguration()->getUid(), [1,2]);

        $processMarker = $this->markerReducer->explode($surveyRequestDb->getProcess());
        self::assertSame($order, $processMarker['process']);

        $processSubjectMarker = $this->markerReducer->explode($surveyRequestDb->getProcessSubject());
        self::assertSame($surveyRequestDb->getSurveyConfiguration()->getProduct(), $processSubjectMarker['processSubject']);
    }


    /**
     * @test
     * @throws \Exception
     */
    public function createSurveyRequestDoesNotPersistNewSurveyRequestIfOrderDoesNotMatchSurveyConfigurationProduct(): void
    {
        /**
         * Scenario:
         *
         * Given an order-object that is persisted
         * Given a targetGroup-object 1 that is attached to that order-object
         * Given an orderItem-object that belongs to that order-object
         * Given a product-object 1 that belongs to that orderItem-object
         * Given a product-object 2
         * Given a surveyConfiguration-object that is persisted
         * Given the surveyConfiguration-property product is set to product-object 2
         * Given the targetGroup-object 1 attached to the surveyConfiguration
         * When the method is called
         * Then no new surveyRequest-object is persisted
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check20.xml');

        /** @var \RKW\RkwShop\Domain\Model\Order $order */
        $order = $this->orderRepository->findByUid(1);

        /** @var  \TYPO3\CMS\Extbase\Persistence\QueryResultInterface $surveyRequests */
        $surveyRequestsDb = $this->surveyRequestRepository->findAll();
        self::assertCount(0, $surveyRequestsDb);

        $this->fixture->createSurveyRequest($order);

        /** @var  \TYPO3\CMS\Extbase\Persistence\QueryResultInterface $surveyRequests */
        $surveyRequestsDb = $this->surveyRequestRepository->findAll();
        self::assertCount(0, $surveyRequestsDb);
    }


    /**
     * @test
     * @throws \Exception
     */
    public function createSurveyRequestDoesNotPersistNewSurveyRequestIfOrderDoesNotMatchSurveyConfigurationTargetGroup(): void
    {
        /**
         * Scenario:
         *
         * Given an order-object that is persisted
         * Given a targetGroup-object 1 that is attached to that order-object
         * Given an orderItem-object that belongs to that order-object
         * Given a product-object 1 that belongs to that orderItem-object
         * Given a targetGroup-object 2
         * Given a surveyConfiguration-object that is persisted
         * Given the surveyConfiguration-property product is set to product-object 1
         * Given the targetGroup-object 2 attached to the surveyConfiguration
         * When the method is called
         * Then no new surveyRequest-object is persisted
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check30.xml');

        /** @var \RKW\RkwShop\Domain\Model\Order $order */
        $order = $this->orderRepository->findByUid(1);

        $this->fixture->createSurveyRequest($order);

        /** @var  \TYPO3\CMS\Extbase\Persistence\QueryResultInterface $surveyRequests */
        $surveyRequestsDb = $this->surveyRequestRepository->findAll();
        self::assertCount(0, $surveyRequestsDb);
    }


    /**
     * @test
     * @throws \Exception
     */
    public function createSurveyRequestSetsOrderReferenceOnPersistedNewSurveyRequest(): void
    {
        /**
         * Scenario:
         *
         * Given an order-object that is persisted
         * Given a targetGroup-object 1 that is attached to that order-object
         * Given an orderItem-object that belongs to that order-object
         * Given a product-object that belongs to that orderItem-object
         * Given a surveyConfiguration-object that is persisted
         * Given the surveyConfiguration-property product is set to the same product-object as the orderItem-property product
         * Given the targetGroup-object 1 attached to the surveyConfiguration
         * When the method is called
         * Then a new surveyRequest-object is persisted
         * Then the surveyRequest-property order is set to that order-object
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check40.xml');

        /** @var \RKW\RkwShop\Domain\Model\Order $order */
        $order = $this->orderRepository->findByUid(1);

        $this->fixture->createSurveyRequest($order);

        /** @var  \TYPO3\CMS\Extbase\Persistence\QueryResultInterface $surveyRequests */
        $surveyRequestsDb = $this->surveyRequestRepository->findAll();
        self::assertCount(1, $surveyRequestsDb);

        /** @var \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequest */
        $surveyRequestDb = $surveyRequestsDb->getFirst();
        self::assertInstanceOf(SurveyRequest::class, $surveyRequestDb);

        $processMarker = $this->markerReducer->explode($surveyRequestDb->getProcess());

        self::assertSame($order, $processMarker['process']);
        self::assertInstanceOf(\RKW\RkwShop\Domain\Model\Order::class, $processMarker['process']);
        self::assertEquals($order->getUid(), $processMarker['process']->getUid());

    }


    /**
     * @throws \Exception
     * @todo Ist durch MarkerReducer ersetzt worden!
     */
    public function createSurveyRequestSetsProcessTypeOnPersistedNewSurveyRequest(): void
    {
        /**
         * Scenario:
         *
         * Given an order-object that is persisted
         * Given a targetGroup-object 1 that is attached to that order-object
         * Given an orderItem-object that belongs to that order-object
         * Given a product-object that belongs to that orderItem-object
         * Given a surveyConfiguration-object that is persisted
         * Given the surveyConfiguration-property product is set to the same product-object as the orderItem-property product
         * Given the targetGroup-object 1 attached to the surveyConfiguration
         * When the method is called
         * Then a new surveyRequest-object is persisted
         * Then the surveyRequest-property processType is set to the order-class
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check50.xml');

        /** @var \RKW\RkwShop\Domain\Model\Order $order */
        $order = $this->orderRepository->findByUid(1);

        $this->fixture->createSurveyRequest($order);

        /** @var  \TYPO3\CMS\Extbase\Persistence\QueryResultInterface $surveyRequests */
        $surveyRequestsDb = $this->surveyRequestRepository->findAll();
        self::assertCount(1, $surveyRequestsDb);

        /** @var \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequest */
        $surveyRequestDb = $surveyRequestsDb->getFirst();
        self::assertSame(\RKW\RkwShop\Domain\Model\Order::class, $surveyRequestDb->getProcessType());
    }


    /**
     * @test
     * @throws \Exception
     */
    public function createSurveyRequestSetsFrontendUserOnPersistedNewSurveyRequest(): void
    {
        /**
         * Scenario:
         *
         * Given an order-object that is persisted
         * Given a frontendUser-object that is persisted
         * Given the order-property frontendUser is set to that frontendUser-object
         * Given a targetGroup-object 1 that is attached to that order-object
         * Given an orderItem-object that belongs to that order-object
         * Given a product-object that belongs to that orderItem-object
         * Given a surveyConfiguration-object that is persisted
         * Given the surveyConfiguration-property product is set to the same product-object as the orderItem-property product
         * Given the targetGroup-object 1 attached to the surveyConfiguration
         * When the method is called
         * Then a new surveyRequest-object is persisted
         * Then the surveyRequest-property frontendUser is set to the frontendUser-object attached to the order
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check60.xml');

        /** @var \RKW\RkwShop\Domain\Model\Order $order */
        $order = $this->orderRepository->findByUid(1);

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByUid(1);

        $this->fixture->createSurveyRequest($order);

        /** @var  \TYPO3\CMS\Extbase\Persistence\QueryResultInterface $surveyRequests */
        $surveyRequestsDb = $this->surveyRequestRepository->findAll();

        /** @var \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequest */
        $surveyRequestDb = $surveyRequestsDb->getFirst();
        self::assertSame($frontendUser->getUid(), $surveyRequestDb->getFrontendUser()->getUid());
        self::assertInstanceOf(\Madj2k\FeRegister\Domain\Model\FrontendUser::class, $surveyRequestDb->getFrontendUser());
    }


    /**
     * @test
     * @throws \Exception
     */
    public function createSurveyRequestSetsTargetGroupOnPersistedNewSurveyRequest(): void
    {
        /**
         * Scenario:
         *
         * Given an order-object that is persisted
         * Given the order-property frontendUser is set to that frontendUser-object
         * Given a targetGroup-object 1 that is attached to that order-object
         * Given an orderItem-object that belongs to that order-object
         * Given a product-object that belongs to that orderItem-object
         * Given a surveyConfiguration-object that is persisted
         * Given the surveyConfiguration-property product is set to the same product-object as the orderItem-property product
         * Given the targetGroup-object 1 attached to the surveyConfiguration
         * When the method is called
         * Then a new surveyRequest-object is persisted
         * Then the surveyRequest-property targetGroup is set to the targetGroup-bject 1
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check70.xml');

        /** @var \RKW\RkwShop\Domain\Model\Order $order */
        $order = $this->orderRepository->findByUid(1);

        $this->fixture->createSurveyRequest($order);

        /** @var  \TYPO3\CMS\Extbase\Persistence\QueryResultInterface $surveyRequests */
        $surveyRequestsDb = $this->surveyRequestRepository->findAll();

        /** @var \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequest */
        $surveyRequestDb = $surveyRequestsDb->getFirst();

        $order->getTargetGroup()->rewind();
        $surveyRequestDb->getTargetGroup()->rewind();

        self::assertSame($order->getTargetGroup()->current(), $surveyRequestDb->getTargetGroup()->current());
    }


    /**
     * @todo: Kann weg!
     * @throws \Exception
     */
    public function createSurveyRequestDoesNotSetSurveyConfigurationOnPersistedNewSurveyRequest(): void
    {
        /**
         * Scenario:
         *
         * Given an order-object that is persisted
         * Given the order-property frontendUser is set to that frontendUser-object
         * Given a targetGroup-object 1 that is attached to that order-object
         * Given an orderItem-object that belongs to that order-object
         * Given a product-object that belongs to that orderItem-object
         * Given a surveyConfiguration-object that is persisted
         * Given the surveyConfiguration-property product is set to the same product-object as the orderItem-property product
         * Given the targetGroup-object 1 attached to the surveyConfiguration
         * When the method is called
         * Then a new surveyRequest-object is persisted
         * Then the surveyRequest-property surveyConfiguration is not set
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check80.xml');

        /** @var \RKW\RkwShop\Domain\Model\Order $order */
        $order = $this->orderRepository->findByUid(1);

        $this->fixture->createSurveyRequest($order);

        /** @var  \TYPO3\CMS\Extbase\Persistence\QueryResultInterface $surveyRequests */
        $surveyRequestsDb = $this->surveyRequestRepository->findAll();

        /** @var \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequest */
        $surveyRequestDb = $surveyRequestsDb->getFirst();
        self::assertNull($surveyRequestDb->getSurveyConfiguration());
    }


    /**
     * @todo Kann weg!
     * @throws \Exception
     */
    public function createSurveyRequestDoesNotSetProcessSubjectOnPersistedNewSurveyRequest(): void
    {
        /**
         * Scenario:
         *
         * Given an order-object that is persisted
         * Given the order-property frontendUser is set to that frontendUser-object
         * Given a targetGroup-object 1 that is attached to that order-object
         * Given an orderItem-object that belongs to that order-object
         * Given a product-object that belongs to that orderItem-object
         * Given a surveyConfiguration-object that is persisted
         * Given the surveyConfiguration-property product is set to the same product-object as the orderItem-property product
         * Given the targetGroup-object 1 attached to the surveyConfiguration
         * When the method is called
         * Then a new surveyRequest-object is persisted
         * Then the surveyRequest-property processSubject is not set
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check90.xml');

        /** @var \RKW\RkwShop\Domain\Model\Order $order */
        $order = $this->orderRepository->findByUid(1);

        $this->fixture->createSurveyRequest($order);

        /** @var  \TYPO3\CMS\Extbase\Persistence\QueryResultInterface $surveyRequests */
        $surveyRequestsDb = $this->surveyRequestRepository->findAll();

        /** @var \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequest */
        $surveyRequestDb = $surveyRequestsDb->getFirst();

        self::assertEmpty($surveyRequestDb->getProcessSubject());
    }


    /**
     * @test
     * @throws \Exception
     */
    public function createSurveyRequestPersistsNewSurveyRequestIfOrderWithMultipleOrderItemsContainsAtLeastOneProductMatchingSurveyConfiguration(): void
    {
        /**
         * Scenario:
         *
         * Given an order-object that is persisted
         * Given a targetGroup-object 1 that is attached to that order-object
         * Given an orderItem-object 1 that is persisted and belongs to that order-object
         * Given a product-object 1 that is persisted and belongs to that orderItem-object 1
         * Given an orderItem-object 2 that is persisted and belongs to that order-object
         * Given a product-object 2 that is persisted and belongs to that orderItem-object 2
         * Given a surveyConfiguration-object that is persisted
         * Given the surveyConfiguration-property product is set to product-object 1
         * Given the targetGroup-object 1 attached to the surveyConfiguration
         * When the method is called
         * Then a new surveyRequest-object is persisted
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check100.xml');

        /** @var \RKW\RkwShop\Domain\Model\Order $process */
        $order = $this->orderRepository->findByUid(1);

        $this->fixture->createSurveyRequest($order);

        /** @var  \TYPO3\CMS\Extbase\Persistence\QueryResultInterface $surveyRequests */
        $surveyRequestsDb = $this->surveyRequestRepository->findAll();
        self::assertCount(1, $surveyRequestsDb);
    }


    /**
     * @test
     *
     * @throws \Exception
     */
    public function createSurveyRequestCreatesSurveyRequestTriggeredByAnEventReservation(): void
    {
        /**
         * Scenario:
         *
         * Given a frontendUser-object that is persisted
         * Given an event-object that is persisted
         * Given an eventReservation-object that is persisted and belongs to that event-object
         * Given a frontendUser-object that is persisted and belongs to that eventReservation-object
         * Given a targetGroup-object 1 that is attached to that eventReservation-object
         * Given a surveyConfiguration-object that is persisted
         * Given the surveyConfiguration-property event is set to the same event-object as the eventReservation-property event
         * Given the targetGroup-object 1 attached to the surveyConfiguration
         * When the method is called
         * Then a single surveyRequest-object is persisted
         * Then the process-property of this persisted surveyRequest-object is set to the order-object
         * Then the frontendUser-property of this persisted surveyRequest-object is set to the frontendUser-object
         * Then the targetGroup-object 1 is attached to this persisted surveyRequest-object
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check500.xml');

        /** @var \RKW\RkwEvents\Domain\Model\EventReservation $eventReservation */
        $eventReservation = $this->eventReservationRepository->findByUid(1);

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByUid(1);

        /** @var \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequest */
        $surveyRequest = $this->fixture->createSurveyRequest($eventReservation);

        /** @var  \TYPO3\CMS\Extbase\Persistence\QueryResultInterface $surveyRequests */
        $surveyRequestsDb = $this->surveyRequestRepository->findAll();
        self::assertCount(1, $surveyRequestsDb);

        /** @var \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequest */
        $surveyRequestDb = $surveyRequestsDb->getFirst();

        $processMarker = $this->markerReducer->explode($surveyRequestDb->getProcess());

        self::assertSame($surveyRequest, $surveyRequestDb);
        self::assertInstanceOf(SurveyRequest::class, $surveyRequestDb);
        self::assertSame($eventReservation, $processMarker['process']);
        self::assertInstanceOf(EventReservation::class, $processMarker['process']);
        self::assertEquals($eventReservation->getUid(), $processMarker['process']->getUid());
        self::assertSame($frontendUser->getEmail(), $surveyRequestDb->getFrontendUser()->getEmail());
        self::assertInstanceOf(\RKW\RkwEvents\Domain\Model\FrontendUser::class, $surveyRequestDb->getFrontendUser());

        $eventReservation->getTargetGroup()->rewind();
        $surveyRequest->getTargetGroup()->rewind();

        self::assertSame($eventReservation->getTargetGroup()->current(), $surveyRequestDb->getTargetGroup()->current());
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

