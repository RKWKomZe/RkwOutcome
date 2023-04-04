<?php
namespace RKW\RkwOutcome\Tests\Integration\Manager;

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
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use RKW\RkwBasics\Domain\Repository\TargetGroupRepository;
use RKW\RkwEvents\Domain\Model\EventReservation;
use RKW\RkwEvents\Domain\Repository\EventReservationRepository;
use RKW\RkwOutcome\Domain\Model\SurveyRequest;
use RKW\RkwOutcome\Domain\Repository\SurveyConfigurationRepository;
use RKW\RkwOutcome\Domain\Repository\SurveyRequestRepository;
use RKW\RkwOutcome\Manager\SurveyRequestManager;
use RKW\RkwShop\Domain\Repository\OrderRepository;
use RKW\RkwShop\Domain\Repository\ProductRepository;
use RKW\RkwSurvey\Domain\Repository\SurveyRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

/**
 * SurveyRequestManagerTest
 *
 * @author Christian Dilger <c.dilger@addorange.de>
 * @copyright Rkw Kompetenzzentrum
 * @package RKW_RkwOutcome
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class SurveyRequestManagerTest extends FunctionalTestCase
{

    /**
     * @const
     */
    const FIXTURE_PATH = __DIR__ . '/SurveyRequestManagerTest/Fixtures';


    /**
     * @var string[]
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/rkw_basics',
        'typo3conf/ext/rkw_events',
        'typo3conf/ext/rkw_mailer',
        'typo3conf/ext/rkw_outcome',
        'typo3conf/ext/rkw_registration',
        'typo3conf/ext/rkw_shop',
        'typo3conf/ext/rkw_survey',
        'typo3conf/ext/static_info_tables',
    ];


    /**
     * @var string[]
     */
    protected $coreExtensionsToLoad = [ ];


    /**
     * @var \RKW\RkwOutcome\Manager\SurveyRequestManager
     */
    private $subject;


    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
     */
    private $objectManager;


    /**
     * @var \RKW\RkwShop\Domain\Repository\FrontendUserRepository|\RKW\RkwEvents\Domain\Repository\FrontendUserRepository
     */
    private $frontendUserRepository;


    /**
     * @var \RKW\RkwEvents\Domain\Repository\EventReservationRepository
     */
    private $eventReservationRepository;


    /**
     * @var \RKW\RkwShop\Domain\Repository\OrderRepository
     */
    private $orderRepository;


    /**
     * @var \RKW\RkwEvents\Domain\Repository\EventRepository
     */
    private $eventRepository;


    /**
     * @var \RKW\RkwShop\Domain\Repository\ProductRepository
     */
    private $productRepository;


    /**
     * @var \RKW\RkwBasics\Domain\Repository\TargetGroupRepository
     */
    private $targetGroupRepository;


    /**
     * @var \RKW\RkwOutcome\Domain\Repository\SurveyRequestRepository
     */
    private $surveyRequestRepository;


    /**
     * @var \RKW\RkwOutcome\Domain\Repository\SurveyConfigurationRepository|null
     */
    private $surveyConfigurationRepository;

    /**
     * @var \RKW\RkwSurvey\Domain\Repository\SurveyRepository|null
     */
    private $surveyRepository;

    /**
     * PersistenceManager
     *
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager
     */
    protected $persistenceManager;

    /**
     * @var int
     */
    protected $checkPeriod;

    /**
     * @var int
     */
    protected $maxSurveysPerPeriodAndFrontendUser;


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
                'EXT:rkw_basics/Configuration/TypoScript/constants.typoscript',
                'EXT:rkw_basics/Configuration/TypoScript/setup.typoscript',
                'EXT:rkw_outcome/Configuration/TypoScript/constants.typoscript',
                'EXT:rkw_outcome/Configuration/TypoScript/setup.typoscript',
                'EXT:rkw_registration/Configuration/TypoScript/constants.typoscript',
                'EXT:rkw_registration/Configuration/TypoScript/setup.typoscript',
                'EXT:rkw_shop/Configuration/TypoScript/constants.typoscript',
                'EXT:rkw_shop/Configuration/TypoScript/setup.typoscript',
                'EXT:rkw_survey/Configuration/TypoScript/constants.typoscript',
                'EXT:rkw_survey/Configuration/TypoScript/setup.typoscript',
                self::FIXTURE_PATH . '/Frontend/Configuration/Rootpage.typoscript',
            ]
        );

        /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $this->objectManager */
        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);

        $this->eventRepository = $this->objectManager->get(EventReservationRepository::class);
        $this->eventReservationRepository = $this->objectManager->get(EventReservationRepository::class);
        $this->frontendUserRepository = $this->objectManager->get(\RKW\RkwShop\Domain\Repository\FrontendUserRepository::class);
        $this->orderRepository = $this->objectManager->get(OrderRepository::class);
        $this->persistenceManager = GeneralUtility::makeInstance(PersistenceManager::class);
        $this->processRepository = $this->objectManager->get(\RKW\RkwShop\Domain\Repository\OrderRepository::class);
        $this->productRepository = $this->objectManager->get(ProductRepository::class);
        $this->surveyConfigurationRepository = $this->objectManager->get(SurveyConfigurationRepository::class);
        $this->surveyRepository = $this->objectManager->get(SurveyRepository::class);
        $this->surveyRequestRepository = $this->objectManager->get(SurveyRequestRepository::class);
        $this->targetGroupRepository = $this->objectManager->get(TargetGroupRepository::class);

        $this->subject = $this->objectManager->get(SurveyRequestManager::class);

        // For Mail-Interface
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName'] = 'RKW';
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'] = 'service@mein.rkw.de';
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailReplyName'] = 'RKW';
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailReplyToAddress'] = 'reply@mein.rkw.de';
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailReturnAddress'] = 'bounces@mein.rkw.de';

        $this->checkPeriod = 7 * 24 * 60 * 60;
        $this->maxSurveysPerPeriodAndFrontendUser = 1;

    }


    /**
     * @test
     * @throws \Nimut\TestingFramework\Exception\Exception
     */
    public function createSurveyRequestCreatesSurveyRequestIfOrderContainsAProductAssociatedWithSurveyConfigurationWithSameTargetGroup()
    {

        /**
         * Scenario:
         *
         * Given an order-object that is persisted
         * Given the order-property frontendUser is set to that frontendUser-object
         * Given a frontendUser-object that is persisted and belongs to that order-object
         * Given a targetGroup-object 1 that is attached to that order-object
         * Given an orderItem-object that is persisted and belongs to that order-object
         * Given a product-object is persisted and belongs to that orderItem-object
         * Given a surveyConfiguration-object that is persisted
         * Given the surveyConfiguration-property product is set to the same product-object as the orderItem-property product
         * Given the targetGroup-object 1 attached to the surveyConfiguration
         * When the method is called
         * Then a single surveyRequest-object is persisted
         * Then the order-property of this persisted surveyRequest-object is set to the order-object
         * Then the frontendUser-property of this persisted surveyRequest-object is set to the frontendUser-object
         * Then the targetGroup-object 1 is attached to this persisted surveyRequest-object
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check10.xml');

        /** @var \RKW\RkwShop\Domain\Model\Order $process */
        $order = $this->orderRepository->findByUid(1);

        /** @var \RKW\RkwShop\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByUid(1);

        /** @var \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequest */
        $surveyRequest = $this->subject->createSurveyRequest($order);

        /** @var  \TYPO3\CMS\Extbase\Persistence\QueryResultInterface $surveyRequests */
        $surveyRequestsDb = $this->surveyRequestRepository->findAll();
        self::assertCount(1, $surveyRequestsDb);

        /** @var \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequest */
        $surveyRequestDb = $surveyRequestsDb->getFirst();
        self::assertSame($surveyRequest, $surveyRequestDb);
        self::assertInstanceOf(SurveyRequest::class, $surveyRequestDb);

        self::assertSame($order, $surveyRequestDb->getOrder());
        self::assertInstanceOf(\RKW\RkwShop\Domain\Model\Order::class, $surveyRequestDb->getOrder());
        self::assertSame(\RKW\RkwShop\Domain\Model\Order::class, $surveyRequestDb->getProcessType());
        self::assertSame($frontendUser, $surveyRequest->getFrontendUser());
        self::assertInstanceOf(\RKW\RkwRegistration\Domain\Model\FrontendUser::class, $surveyRequest->getFrontendUser());

        $order->getTargetGroup()->rewind();
        $surveyRequest->getTargetGroup()->rewind();

        self::assertSame($order->getTargetGroup()->current(), $surveyRequest->getTargetGroup()->current());

    }


    /**
     * @test
     * @throws \Nimut\TestingFramework\Exception\Exception
     */
    public function createSurveyRequestCreatesSurveyRequestIfOrderWithMultipleOrderItemsContainsAtLeastOneProductAssociatedWithSurveyConfiguration(): void
    {

        /**
         * Scenario:
         *
         * Given an order-object that is persisted
         * Given the order-property frontendUser is set to that frontendUser-object
         * Given a frontendUser-object that is persisted and belongs to that order-object
         * Given a targetGroup-object 1 that is attached to that order-object
         * Given an orderItem-object 1 that is persisted and belongs to that order-object
         * Given a product-object 1 that is persisted and belongs to that orderItem-object 1
         * Given an orderItem-object 2 that is persisted and belongs to that order-object
         * Given a product-object 2 that is persisted and belongs to that orderItem-object 2
         * Given a surveyConfiguration-object that is persisted
         * Given the surveyConfiguration-property product is set to product-object 1
         * Given the targetGroup-object 1 attached to the surveyConfiguration
         * When the method is called
         * Then a single surveyRequest-object is persisted
         * Then the order-property of this persisted surveyRequest-object is set to the order-object
         * Then the frontendUser-property of this persisted surveyRequest-object is set to the frontendUser-object
         * Then the targetGroup-object 1 is attached to this persisted surveyRequest-object
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check20.xml');

        /** @var \RKW\RkwShop\Domain\Model\Order $process */
        $order = $this->orderRepository->findByUid(1);

        /** @var \RKW\RkwShop\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByUid(1);

        /** @var \RKW\RkwBasics\Domain\Model\TargetGroup $targetGroup */
        $targetGroup = $this->targetGroupRepository->findByUid(1);

        /** @var \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequest */
        $surveyRequest = $this->subject->createSurveyRequest($order);

        /** @var  \TYPO3\CMS\Extbase\Persistence\QueryResultInterface $surveyRequests */
        $surveyRequestsDb = $this->surveyRequestRepository->findAll();
        self::assertCount(1, $surveyRequestsDb);

        /** @var \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequest */
        $surveyRequestDb = $surveyRequestsDb->getFirst();
        self::assertSame($surveyRequest, $surveyRequestDb);
        self::assertInstanceOf(SurveyRequest::class, $surveyRequestDb);

        self::assertSame($order, $surveyRequestDb->getOrder());
        self::assertInstanceOf(\RKW\RkwShop\Domain\Model\Order::class, $surveyRequestDb->getOrder());
        self::assertSame(\RKW\RkwShop\Domain\Model\Order::class, $surveyRequestDb->getProcessType());
        self::assertSame($frontendUser, $surveyRequestDb->getFrontendUser());
        self::assertInstanceOf(\RKW\RkwRegistration\Domain\Model\FrontendUser::class, $surveyRequestDb->getFrontendUser());

        $order->getTargetGroup()->rewind();
        $surveyRequestDb->getTargetGroup()->rewind();

        self::assertSame($order->getTargetGroup()->current(), $surveyRequestDb->getTargetGroup()->current());

    }

    /**
     * @test
     * @throws \Nimut\TestingFramework\Exception\Exception
     */
    public function createSurveyRequestDoesNotCreateSurveyRequestIfNoSurveyIsAssociatedWithContainedProducts()
    {

        /**
         * Scenario:
         *
         * Given an order-object that is persisted
         * Given the order-property frontendUser is set to that frontendUser-object
         * Given a frontendUser-object that is persisted and belongs to that order-object
         * Given a targetGroup-object 1 that is attached to that order-object
         * Given an orderItem-object that is persisted and belongs to that order-object
         * Given a product-object 1 that is persisted and belongs to that orderItem-object
         * Given a product-object 2 that is persisted and does not belong to that order
         * Given a surveyConfiguration-object that is persisted
         * Given the surveyConfiguration-property product is set to product-object 2
         * Given the targetGroup-object 1 attached to the surveyConfiguration
         * When the method is called
         * Then the method returns null
         * Then no surveyRequest-object is persisted
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check30.xml');

        /** @var \RKW\RkwShop\Domain\Model\Order $process */
        $order = $this->orderRepository->findByUid(1);

        /** @var \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequest */
        $surveyRequest = $this->subject->createSurveyRequest($order);
        self::assertNull($surveyRequest);

        /** @var  \TYPO3\CMS\Extbase\Persistence\QueryResultInterface $surveyRequests */
        $surveyRequestsDb = $this->surveyRequestRepository->findAll();
        self::assertCount(0, $surveyRequestsDb);

    }

    /**
     * @test
     * @throws \Nimut\TestingFramework\Exception\Exception
     */
    public function createSurveyRequestDoesNotCreateSurveyRequestIfAssociatedSurveyConfigurationIsNotSetToSameTargetGroupAsOrder(): void
    {

        /**
         * Scenario:
         *
         * Given a targetGroup-object 1 is persisted
         * Given a targetGroup-object 2 is persisted
         * Given an order-object that is persisted
         * Given the targetGroup-object 1 is attached to that order-object
         * Given an orderItem-object that is persisted and belongs to that order-object
         * Given a product-object is persisted and belongs to that orderItem-object
         * Given a surveyConfiguration-object is persisted
         * Given the product-property of that surveyConfiguration-object is that product-object
         * Given the targetGroup-object 2 is attached to that surveyConfiguration-object
         * When the method is called
         * Then the method returns null
         * Then no surveyRequest-object is persisted
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check40.xml');

        /** @var \RKW\RkwShop\Domain\Model\Order $process */
        $order = $this->orderRepository->findByUid(1);

        /** @var \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequest */
        $surveyRequest = $this->subject->createSurveyRequest($order);
        self::assertNull($surveyRequest);

        /** @var  \TYPO3\CMS\Extbase\Persistence\QueryResultInterface $surveyRequests */
        $surveyRequestsDb = $this->surveyRequestRepository->findAll();
        self::assertCount(0, $surveyRequestsDb);

    }

    /**
     * @throws \Nimut\TestingFramework\Exception\Exception
     */
    public function createSurveyRequestCreatesSurveyRequestTriggeredByAnEventReservation()
    {

        /**
         * Scenario:
         *
         * Given an event-object that is persisted
         * Given an eventReservation-object that is persisted and belongs to that event-object
         * Given a survey is associated with event-object
         * Given the surveyConfiguration-property targetGroup is set to the eventReservation-property targetGroup
         * When the method is called
         * Then an instance of \RKW\RkwOutcome\Model\SurveyRequest is returned
         * Then the process-property of this instance is set to the eventReservation-object
         * Then the frontendUser-property of this instance is set to the frontendUser-object
         * Then the targetGroup-property of this instance is set to targetGroup-property of the order-object
         * Then the surveyRequest-object is persisted
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check45.xml');

        /** @var \RKW\RkwEvents\Domain\Model\EventReservation $order */
        $order = $this->orderRepository->findByUid(1);

        /** @var \RKW\RkwEvents\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByUid(1);

        /** @var \RKW\RkwBasics\Domain\Model\TargetGroup $targetGroup */
        $targetGroup = $this->targetGroupRepository->findByUid(1);

        //  @todo: Darf ein per SignalSlot angesprochene Methode überhaupt etwas zurückliefern?
        /** @var \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequest */
        $surveyRequest = $this->subject->createSurveyRequest($order);

        self::assertInstanceOf(SurveyRequest::class, $surveyRequest);
        self::assertSame($process, $surveyRequest->getProcess());
        self::assertSame(get_class($process), $surveyRequest->getProcessType());
        self::assertSame($frontendUser, $surveyRequest->getFrontendUser());
        self::assertInstanceOf(\RKW\RkwRegistration\Domain\Model\FrontendUser::class, $surveyRequest->getFrontendUser());
        self::assertSame($targetGroup, $surveyRequest->getTargetGroup());

        /** @var  \TYPO3\CMS\Extbase\Persistence\QueryResultInterface $surveyRequests */
        $surveyRequestsDb = $this->surveyRequestRepository->findAll();
        self::assertCount(1, $surveyRequestsDb);

        /** @var \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequest */
        $surveyRequestDb = $surveyRequestsDb->getFirst();
        self::assertSame($surveyRequest, $surveyRequestDb);
        self::assertSame($process, $surveyRequestDb->getProcess());
        self::assertInstanceOf(EventReservation::class, $surveyRequestDb->getProcess());

    }


    //  @todo: Check on Marketing-Häkchen!!! Wo setzen wir das überhaupt? Erst in der 9.5!!!
    //  @todo: Check, if existing survey is connected to same target group
    //  @todo: Check, if connected survey is due (in between starttime <> endtime)

    /**
     * @throws \Nimut\TestingFramework\Exception\Exception
     */
    public function createSurveyRequestTriggeredByAnEventDoesNotCreateSurveyRequestIfNoSurveyIsAssociatedWithContainedEvent()
    {

    }



    /**
     * @throws \Nimut\TestingFramework\Exception\Exception
     */
    public function createSurveyRequestTriggeredByAnEventDoesNotCreateSurveyRequestIfAssociatedSurveyDoesNotUseSameTargetgroupAsEventReservation()
    {


    }


    /**
     * @test
     * @throws \Nimut\TestingFramework\Exception\Exception
     * @throws IllegalObjectTypeException
     */
    public function processPendingSurveyRequestMarksProcessedSurveyRequestAsNotifiedIfShippedTstampIsLessThanNowMinusSurveyWaitingTime(): void
    {

        /**
         * Scenario:
         *
         * Given the surveyWaitingTime is set to 1 * 24 * 60 * 60 seconds (1 day)
         * Given a persisted survey
         * Given a persisted product
         * Given a persisted surveyConfiguration-object
         * Given the surveyConfiguration-property survey is set to that survey-object
         * Given the surveyConfiguration-property product is set to that product-object
         * Given the targetGroup-object 1 is attached to that surveyConfiguration-object
         * Given a persisted order-object
         * Given the order-property shippedTstamp is set to greater than (now (time()) - surveyWaitingTime (1 day))
         * Given the product-property ot the contained orderItem-object is set to the same product-object
         * Given the targetGroup-object 1 is attached to that order-object
         * Given a persisted surveyRequest-object
         * Given the surveyRequest-property process is set to that order-object
         * When the method is called
         * Then the surveyRequest-property notifiedTstamp is set to > 0
         * Then the surveyRequest-property processSubject is set to that product-object
         * Then the surveyRequest-property survey is set to that survey-object
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check50.xml');

        /** @var \RKW\RkwShop\Domain\Model\Order $order */
        $order = $this->orderRepository->findByUid(1);
        $order->setShippedTstamp(strtotime('-2 days'));
        $this->orderRepository->update($order);

        //  workaround - add order as Order-Object to SurveyRequest, as it is not working via Fixture due to process = AbstractEntity
        $this->setUpSurveyRequest('\RKW\RkwShop\Domain\Model\Order');

        $notifiedSurveyRequests = $this->subject->processPendingSurveyRequests(
            $checkPeriod = $this->checkPeriod,
            $maxSurveysPerPeriodAndFrontendUser = $this->maxSurveysPerPeriodAndFrontendUser,
            $surveyWaitingTime = (1 * 24 * 60 * 60)
        );

        self::assertCount(1, $notifiedSurveyRequests);

        /** @var \RKW\RkwSurvey\Domain\Model\Survey $surveyDb */
        $surveyDb = $this->surveyRepository->findByUid(1);

        /** @var \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequestDb */
        $surveyRequestDb = $this->surveyRequestRepository->findByUid(1);
        self::assertGreaterThan(0, $surveyRequestDb->getNotifiedTstamp());
        self::assertInstanceOf(\RKW\RkwShop\Domain\Model\Order::class, $surveyRequestDb->getOrder());
        $order->getOrderItem()->rewind();
        self::assertSame($order->getOrderItem()->current()->getProduct(), $surveyRequestDb->getProcessSubject());
        self::assertSame($surveyDb, $surveyRequestDb->getSurvey());
    }


    /**
     * @test
     * @throws \Nimut\TestingFramework\Exception\Exception
     */
    public function processPendingSurveyRequestDoesNotMarkProcessedSurveyRequestAsNotifiedIfShippedTstampIsGreaterThanNowMinusSurveyWaitingTime()
    {

        /**
         * Scenario:
         *
         * Given the surveyWaitingTime is set to 2 * 24 * 60 * 60 seconds (2 days)
         * Given a persisted survey
         * Given a persisted product
         * Given a persisted surveyConfiguration-object
         * Given the surveyConfiguration-property survey is set to that survey-object
         * Given the surveyConfiguration-property product is set to that product-object
         * Given the targetGroup-object 1 is attached to that surveyConfiguration-object
         * Given a persisted order-object
         * Given the order-property shippedTstamp is set to greater than (now (time()) - surveyWaitingTime (2 days))
         * Given the product-property ot the contained orderItem-object is set to the same product-object
         * Given the targetGroup-object 1 is attached to that order-object
         * Given a persisted surveyRequest-object
         * Given the surveyRequest-property process is set to that order-object
         * When the method is called
         * Then the surveyRequest-property notifiedTstamp remains 0
         * Then the surveyRequest-property processSubject remains null
         * Then the surveyRequest-property survey remains null
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check70.xml');

        /** @var \RKW\RkwShop\Domain\Model\Order $order */
        $order = $this->orderRepository->findByUid(1);
        $order->setShippedTstamp(strtotime('-1 day'));
        $this->orderRepository->update($order);
        $this->persistenceManager->persistAll();

        //  workaround - add order as Order-Object to SurveyRequest, as it is not working via Fixture due to process = AbstractEntity
        $this->setUpSurveyRequest('\RKW\RkwShop\Domain\Model\Order');

        $notifiedSurveyRequests = $this->subject->processPendingSurveyRequests(
            $checkPeriod = $this->checkPeriod,
            $maxSurveysPerPeriodAndFrontendUser = $this->maxSurveysPerPeriodAndFrontendUser,
            $surveyWaitingTime = (2 * 24 * 60 * 60)
        );
        self::assertCount(0, $notifiedSurveyRequests);

        /** @var \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequestDb */
        $surveyRequestDb = $this->surveyRequestRepository->findByUid(1);
        self::assertSame(0, $surveyRequestDb->getNotifiedTstamp());
        self::assertSame(null, $surveyRequestDb->getProcessSubject());
        self::assertSame(null, $surveyRequestDb->getSurvey());

    }


    /**
     * @test
     * @throws \Nimut\TestingFramework\Exception\Exception
     * @throws IllegalObjectTypeException
     */
    public function processPendingSurveyRequestSetsProcessedSurveyRequestPropertyProcessSubjectToSingleProductAssociatedWithMatchingSurveyConfigurationIfOtherProductsExist()
    {

        /**
         * Scenario:
         *
         * Given a persisted product
         * Given a persisted surveyConfiguration-object
         * Given the surveyConfiguration-property is set to that product-object
         * Given a second persisted product
         * Given a persisted order-object
         * Given the order-object contains two orderItem-objects
         * Given the product-property of the first orderItem-object is set to the first product-object
         * Given the product-property of the second orderItem-object is set to the second product-object
         * Given a persisted surveyRequest-object
         * Given the surveyRequest-property process is set to that order
         * When the method is called
         * Then the surveyRequest-property notifiedTstamp is set to > 0
         * Then the surveyRequest-property processSubject is set to the first product-object set in first orderItem-object
         * Then the surveyRequest-property processSubject is not set to the second product-object set in second orderItem-object
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check55.xml');

        //  dynamically set shippedTstamp to be less than time() - $surveyWaitingTime
        /** @var \RKW\RkwShop\Domain\Model\Order $order */
        $order = $this->orderRepository->findByUid(1);
        $order->setShippedTstamp(strtotime('-2 days'));
        $this->orderRepository->update($order);

        //  workaround - add order as Order-Object to SurveyRequest, as it is not working via Fixture due to process = AbstractEntity
        $this->setUpSurveyRequest('\RKW\RkwShop\Domain\Model\Order');

        $notifiedSurveyRequests = $this->subject->processPendingSurveyRequests(
            $checkPeriod = $this->checkPeriod,
            $maxSurveysPerPeriodAndFrontendUser = $this->maxSurveysPerPeriodAndFrontendUser,
            $surveyWaitingTime = (1 * 24 * 60 * 60)
        );
        self::assertCount(1, $notifiedSurveyRequests);

        /** @var \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequestDb */
        $surveyRequestDb = $this->surveyRequestRepository->findByUid(1);
        self::assertGreaterThan(0, $surveyRequestDb->getNotifiedTstamp());
        self::assertInstanceOf(\RKW\RkwShop\Domain\Model\Order::class, $surveyRequestDb->getOrder());

        self::assertSame(1, $surveyRequestDb->getProcessSubject()->getUid());
        self::assertNotSame(2, $surveyRequestDb->getProcessSubject()->getUid());
        self::assertSame(2, $surveyRequestDb->getSurvey()->getUid());

    }

    /**
     * @test
     * @throws \Nimut\TestingFramework\Exception\Exception
     */
    public function processPendingSurveyRequestSetsProcessedSurveyRequestPropertyProcessSubjectToSingleProductAssociatedWithMatchingSurveyConfigurationEvenIfASecondProductWithNotMatchingSurveyConfigurationExists()
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
         * Given the surveyRequest-property process is set to that order
         * When the method is called
         * Then the surveyRequest-property notifiedTstamp is set to > 0
         * Then the surveyRequest-property processSubject is set to the product-object 1
         * Then the surveyRequest-property processSubject is not set to the product-object 2
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check100.xml');

        //  dynamically set shippedTstamp to be less than time() - $surveyWaitingTime
        /** @var \RKW\RkwShop\Domain\Model\Order $order */
        $order = $this->orderRepository->findByUid(1);
        $order->setShippedTstamp(strtotime('-2 days'));
        $this->orderRepository->update($order);

        //  workaround - add order as Order-Object to SurveyRequest, as it is not working via Fixture due to process = AbstractEntity
        $this->setUpSurveyRequest('\RKW\RkwShop\Domain\Model\Order');

        $notifiedSurveyRequests = $this->subject->processPendingSurveyRequests(
            $checkPeriod = $this->checkPeriod,
            $maxSurveysPerPeriodAndFrontendUser = $this->maxSurveysPerPeriodAndFrontendUser,
            $surveyWaitingTime = (1 * 24 * 60 * 60)
        );
        self::assertCount(1, $notifiedSurveyRequests);

        /** @var \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequestDb */
        $surveyRequestDb = $this->surveyRequestRepository->findByUid(1);
        self::assertGreaterThan(0, $surveyRequestDb->getNotifiedTstamp());
        self::assertInstanceOf(\RKW\RkwShop\Domain\Model\Order::class, $surveyRequestDb->getOrder());

        self::assertSame(1, $surveyRequestDb->getProcessSubject()->getUid());
        self::assertNotSame(2, $surveyRequestDb->getProcessSubject()->getUid());
        self::assertSame(2, $surveyRequestDb->getSurvey()->getUid());

    }


    /**
     * @test
     * @throws \Nimut\TestingFramework\Exception\Exception
     */
    public function processPendingSurveyRequestSetsSurveyRequestPropertyProcessSubjectToRandomProductAssociatedWithSurveyConfiguration()
    {

        /**
         * Scenario:
         *
         * Given a first persisted product
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
         * Given the surveyRequest-property process is set to that order
         * When the method is called
         * Then the surveyRequest-property notifiedTstamp is set to > 0
         * Then the surveyRequest-property processSubject is not set to the third product-object set in third orderItem-object
         * Then the surveyRequest-property processSubject is set either to the first product-object set first orderItem-object
         * Or to the second product-object set second orderItem-object
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check90.xml');

        //  dynamically set shippedTstamp to be less than time() - $surveyWaitingTime
        /** @var \RKW\RkwShop\Domain\Model\Order $order */
        $order = $this->orderRepository->findByUid(1);
        $order->setShippedTstamp(strtotime('-2 days'));
        $this->orderRepository->update($order);

        //  workaround - add order as Order-Object to SurveyRequest, as it is not working via Fixture due to process = AbstractEntity
        $this->setUpSurveyRequest('\RKW\RkwShop\Domain\Model\Order');

        $notifiedSurveyRequests = $this->subject->processPendingSurveyRequests(
            $checkPeriod = $this->checkPeriod,
            $maxSurveysPerPeriodAndFrontendUser = $this->maxSurveysPerPeriodAndFrontendUser,
            $surveyWaitingTime = (1 * 24 * 60 * 60)
        );
        self::assertCount(1, $notifiedSurveyRequests);

        /** @var \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequestDb */
        $surveyRequestDb = $this->surveyRequestRepository->findByUid(1);
        self::assertGreaterThan(0, $surveyRequestDb->getNotifiedTstamp());
        self::assertInstanceOf(\RKW\RkwShop\Domain\Model\Order::class, $surveyRequestDb->getOrder());

        self::assertGreaterThan(0, $surveyRequestDb->getProcessSubject()->getUid());
        self::assertNotEquals(3, $surveyRequestDb->getProcessSubject()->getUid());

        self::assertContains($surveyRequestDb->getProcessSubject()->getUid(), [1,2]);
        self::assertContains($surveyRequestDb->getSurvey()->getUid(), [1,2]);

    }

    /**
     * @todo
     *
     * @throws \Nimut\TestingFramework\Exception\Exception
     */
    public function processPendingSurveyRequestsSendsTwoNotificationsIfTwoSeparateUsersTriggeredRequests()
    {


    }

    /**
     * @test
     * @throws \Nimut\TestingFramework\Exception\Exception
     */
    public function processPendingSurveyRequestIgnoresAlreadyProcessedSurveyRequest(): void
    {

        /**
         * Scenario:
         *
         * Given a persisted surveyRequest-object 1
         * Given property notifiedTstamp of surveyRequest-object 1 is set to 1
         * Given a persisted surveyRequest-object 2
         * When the method is called
         * Then the number of processed requests is 1
         * Then the property notifiedTstamp of surveyRequest-object 1 remains 1
         * Then the property notifiedTstamp of surveyRequest-object 2 is greater than 1
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check110.xml');

        //  dynamically set shippedTstamp to be less than time() - $surveyWaitingTime
        /** @var \RKW\RkwShop\Domain\Model\Order $order */
        $order = $this->orderRepository->findByUid(1);
        $order->setShippedTstamp(strtotime('-10 days'));
        $this->orderRepository->update($order);

        $order = $this->orderRepository->findByUid(2);
        $order->setShippedTstamp(strtotime('-2 days'));
        $this->orderRepository->update($order);

        //  workaround - add order as Order-Object to SurveyRequest, as it is not working via Fixture due to process = AbstractEntity
        $this->setUpSurveyRequest('\RKW\RkwShop\Domain\Model\Order', 1);
        $this->setUpSurveyRequest('\RKW\RkwShop\Domain\Model\Order', 2);

        /** @var \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequestAlreadyNotifiedDb */
        $surveyRequestAlreadyNotifiedDb = $this->surveyRequestRepository->findByUid(1);
        $surveyRequestAlreadyNotifiedDb->setNotifiedTstamp(1);
        $this->surveyRequestRepository->update($surveyRequestAlreadyNotifiedDb);
        $this->persistenceManager->persistAll();

        $notifiedSurveyRequests = $this->subject->processPendingSurveyRequests(
            $checkPeriod = $this->checkPeriod,
            $maxSurveysPerPeriodAndFrontendUser = $this->maxSurveysPerPeriodAndFrontendUser,
            $surveyWaitingTime = (1 * 24 * 60 * 60)
        );
        self::assertCount(1, $notifiedSurveyRequests);

        /** @var \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequestProcessedDb */
        $surveyRequestProcessedDb = $this->surveyRequestRepository->findByUid(2);
        self::assertGreaterThan(1, $surveyRequestProcessedDb->getNotifiedTstamp());

    }

    /**
     * @test
     *
     * @throws \Nimut\TestingFramework\Exception\Exception
     */
    public function processPendingSurveyRequestMarksAllConsideredSurveyRequestsAsNotifiedAndSetsProcessSubjectOnlyInSurveyRequestContainingTheSelectedProduct()
    {

        /**
         * Scenario:
         *
         * Given a persisted surveyRequest-object 1
         * Given a persisted order-object 1 that belongs to surveyRequest-object 1
         * Given a persisted surveyRequest-object 2
         * Given a persisted order-object 2 that belongs to surveyRequest-object 2
         * When the method is called
         * Then the number of processed requests is 2
         * Then the property notifiedTstamp of both surveyRequest-objects is greater than 1
         * Then the property notifiedTstamp of both surveyRequest-objects is the same
         * Then the property processedSubject of only one surveyRequest-object is set
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check120.xml');

        //  dynamically set shippedTstamp to be less than time() - $surveyWaitingTime
        /** @var \RKW\RkwShop\Domain\Model\Order $order */
        $order = $this->orderRepository->findByUid(1);
        $order->setShippedTstamp(strtotime('-2 days'));
        $this->orderRepository->update($order);

        $order = $this->orderRepository->findByUid(2);
        $order->setShippedTstamp(strtotime('-2 days'));
        $this->orderRepository->update($order);

        //  workaround - add order as Order-Object to SurveyRequest, as it is not working via Fixture due to process = AbstractEntity
        $this->setUpSurveyRequest('\RKW\RkwShop\Domain\Model\Order', 1);
        $this->setUpSurveyRequest('\RKW\RkwShop\Domain\Model\Order', 2);

        $notifiedSurveyRequests = $this->subject->processPendingSurveyRequests(
            $checkPeriod = $this->checkPeriod,
            $maxSurveysPerPeriodAndFrontendUser = $this->maxSurveysPerPeriodAndFrontendUser,
            $surveyWaitingTime = (1 * 24 * 60 * 60)
        );
        self::assertCount(2, $notifiedSurveyRequests);

        /** @var \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequestProcessedDbUid1 */
        $surveyRequestProcessedDbUid1 = $this->surveyRequestRepository->findByUid(1);
        self::assertGreaterThan(0, $surveyRequestProcessedDbUid1->getNotifiedTstamp());

        /** @var \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequestProcessedDbUid2 */
        $surveyRequestProcessedDbUid2 = $this->surveyRequestRepository->findByUid(2);
        self::assertSame($surveyRequestProcessedDbUid1->getNotifiedTstamp(), $surveyRequestProcessedDbUid2->getNotifiedTstamp());

        self::assertNotSame($surveyRequestProcessedDbUid1->getProcessSubject(), $surveyRequestProcessedDbUid2->getProcessSubject());
        self::assertNotSame($surveyRequestProcessedDbUid1->getSurvey(), $surveyRequestProcessedDbUid2->getSurvey());

        foreach ($notifiedSurveyRequests as $surveyRequest) {
            if ($surveyRequest->getProcessSubject()) {
                self::assertSame($surveyRequest->getUid(), $surveyRequest->getProcessSubject()->getUid());
            } else {
                self::assertNull($surveyRequest->getProcessSubject());
            }
        }

    }

    /**
     * @test
     *
     * @throws \Nimut\TestingFramework\Exception\Exception
     */
    public function processPendingSurveyRequestsRespectsSeparateFrontendUsers()
    {

        /**
         * Scenario:
         *
         * Given a persisted frontendUser 1
         * Given a persisted surveyRequest-object 1 that belongs to frontendUser-object 1
         * Given a persisted order-object 1 that belongs to surveyRequest-object 1
         * Given a persisted product-object 1 that belongs to order-object 1
         * Given a persisted frontendUser 2
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

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check130.xml');

        //  dynamically set shippedTstamp to be less than time() - $surveyWaitingTime
        /** @var \RKW\RkwShop\Domain\Model\Order $order */
        $order = $this->orderRepository->findByUid(1);
        $order->setShippedTstamp(strtotime('-2 days'));
        $this->orderRepository->update($order);

        $order = $this->orderRepository->findByUid(2);
        $order->setShippedTstamp(strtotime('-2 days'));
        $this->orderRepository->update($order);

        //  workaround - add order as Order-Object to SurveyRequest, as it is not working via Fixture due to process = AbstractEntity
        $this->setUpSurveyRequest('\RKW\RkwShop\Domain\Model\Order', 1);
        $this->setUpSurveyRequest('\RKW\RkwShop\Domain\Model\Order', 2);

        $notifiedSurveyRequests = $this->subject->processPendingSurveyRequests(
            $checkPeriod = $this->checkPeriod,
            $maxSurveysPerPeriodAndFrontendUser = $this->maxSurveysPerPeriodAndFrontendUser,
            $surveyWaitingTime = (1 * 24 * 60 * 60)
        );
        self::assertCount(2, $notifiedSurveyRequests);

        /** @var \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequestProcessedDbUid1 */
        $surveyRequestProcessedDbUid1 = $this->surveyRequestRepository->findByUid(1);
        self::assertGreaterThan(0, $surveyRequestProcessedDbUid1->getNotifiedTstamp());
        self::assertSame(1, $surveyRequestProcessedDbUid1->getProcessSubject()->getUid());
        self::assertSame(1, $surveyRequestProcessedDbUid1->getSurvey()->getUid());

        /** @var \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequestProcessedDbUid2 */
        $surveyRequestProcessedDbUid2 = $this->surveyRequestRepository->findByUid(2);
        self::assertGreaterThan(0, $surveyRequestProcessedDbUid2->getNotifiedTstamp());
        self::assertSame(2, $surveyRequestProcessedDbUid2->getProcessSubject()->getUid());
        self::assertSame(2, $surveyRequestProcessedDbUid2->getSurvey()->getUid());

    }

    /**
     * @test
     *
     * @throws \Nimut\TestingFramework\Exception\Exception
     */
    public function processPendingSurveyRequestsRespectsSurveyTimeSlotAndSurveryPerTimeSlotAndFrontendUser(): void
    {

        /**
         * Scenario:
         *
         * Given checkPeriod is set to 7 * 24 * 60 * 60 (last 7 days)
         * Given maxSurveysPerPeriodAndFrontendUser is set to 1
         * Given a persisted frontendUser 1
         * Given a persisted surveyRequest-object 1 that belongs to frontendUser-object 1
         * Given a persisted surveyRequest-object 2 that belongs to frontendUser-object 1
         * When the method is called
         * Then the number of processed requests is 2
         * Given a persisted surveyRequest-object 3 that belongs to frontendUser-object 1
         * When the method is called
         * Then the number of processed requests is 0
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check140.xml');

        //  set now to initial shipping date = first workday of the year
        $initialDate = Carbon::create(2023, 1, 2, 0, 0);
        Carbon::setTestNow($initialDate);

        //  First two orders shipped on $initialDate
        /** @var \RKW\RkwShop\Domain\Model\Order $order */
        $order = $this->orderRepository->findByUid(1);
        $order->setShippedTstamp(Carbon::now()->timestamp);
        $this->orderRepository->update($order);

        /** @var \RKW\RkwShop\Domain\Model\Order $order */
        $order = $this->orderRepository->findByUid(2);
        $order->setShippedTstamp(Carbon::now()->timestamp);
        $this->orderRepository->update($order);

        //  workaround - add order as Order-Object to SurveyRequest, as it is not working via Fixture due to process = AbstractEntity
        $this->setUpSurveyRequest('\RKW\RkwShop\Domain\Model\Order', 1);
        $this->setUpSurveyRequest('\RKW\RkwShop\Domain\Model\Order', 2);

        //  Processing pending survey requests on $initialDate + 1 day
        $notifiedSurveyRequests = $this->subject->processPendingSurveyRequests(
            $checkPeriod = $this->checkPeriod,
            $maxSurveysPerPeriodAndFrontendUser = $this->maxSurveysPerPeriodAndFrontendUser,
            $surveyWaitingTime = 0,
            $currentTime = Carbon::now()->addDays(1)->timestamp
        );
        self::assertCount(2, $notifiedSurveyRequests);

        //  Third order on $initialDate + 2 days
        /** @var \RKW\RkwShop\Domain\Model\Order $order */
        $order = $this->orderRepository->findByUid(2);
        $order->setShippedTstamp(Carbon::now()->addDays(2)->timestamp);
        $this->orderRepository->update($order);

        //  workaround - add order as Order-Object to SurveyRequest, as it is not working via Fixture due to process = AbstractEntity
        $this->setUpSurveyRequest('\RKW\RkwShop\Domain\Model\Order', 3);

        //  Processing pending survey requests on $initialDate + 3 days
        $notifiedSurveyRequests = $this->subject->processPendingSurveyRequests(
            $checkPeriod = $this->checkPeriod,
            $maxSurveysPerPeriodAndFrontendUser = $this->maxSurveysPerPeriodAndFrontendUser,
            $surveyWaitingTime = 0,
            $currentTime = Carbon::now()->addDays(3)->timestamp
        );
        self::assertCount(0, $notifiedSurveyRequests);

        /** @var  \TYPO3\CMS\Extbase\Persistence\QueryResultInterface $surveyRequests */
        $pendingSurveyRequestsDb = $this->surveyRequestRepository->findAllPendingSurveyRequests();
        self::assertCount(1, $pendingSurveyRequestsDb);

    }


    /**
     * @throws \Nimut\TestingFramework\Exception\Exception
     */
    public function processPendingSurveyRequestMarksProcessedSurveyRequestAsNotifiedIfSurveyRequestContainsAnEvent()
    {

        // @todo: Theoretisch könnten die Felder shipped_tstamp und target_group auch in der
        // für die Tabelle tx_rkwshop_domain_model_order in der rkw_outcome angelegt werden?

        //  CommandController->processPendingSurveyRequestsCommand
        //  findAllPendingSurveyRequests
        //  @todo finde alle SurveyRequests im passenden Zeitraum
        //  @todo filtere alle Formate mit zugeordneter Umfrage
        //  @todo filtere auf passende Zielgruppe
        //  @todo checke den shippedTstamp (derzeit = crdate, später per SOAP)
        //  @todo gruppiere nach Format
        //  @todo ggfs. zufällige Auswahl aus der Gruppierung nach Format
        //  foreach found PendingSurveyRequest
        //  processSurveyRequest($surveyRequest)->returns true/false
        //  sendNotification()->returns true/false
        //  setNotifiedTstamp(time())
        //  setProcessSubject(product, event)
        //  endforeach

        /**
         * Scenario:
         *
         * @todo Given the surveyWaitingTime is set to 172800 (2 days)
         * Given a persisted event
         * Given a persisted surveyConfiguration-object
         * Given the surveyConfiguration-property is set to that event-object
         * Given a persisted eventReservation-object
         * Given the event-property of that eventReservation-object is set to the that event-object
         * Given a persisted surveyRequest-object
         * @todo: Check targetGroup
         * Given the surveyRequest-property process is set to that eventReservation-object
         * @todo Given the order-property shipped_tstamp is set to now - surveyWaitingTime
         * When the method is called
         * Then the surveyRequest-property notifiedTstamp is set to > 0
         * Then the surveyRequest-property processSubject is set to that eventReservation-object
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check60.xml');

        //  workaround - add order as Order-Object to SurveyRequest, as it is not working via Fixture due to process = AbstractEntity
        $this->setUpSurveyRequest('\RKW\RkwEvents\Domain\Model\EventReservation');

        $notifiedSurveyRequests = $this->subject->processPendingSurveyRequests(
            $checkPeriod = $this->checkPeriod,
            $maxSurveysPerPeriodAndFrontendUser = $this->maxSurveysPerPeriodAndFrontendUser,
            $surveyWaitingTime = 0
        );
        self::assertCount(1, $notifiedSurveyRequests);

        /** @var \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequestDb */
        $surveyRequestDb = $this->surveyRequestRepository->findByUid(1);
        self::assertGreaterThan(0, $surveyRequestDb->getNotifiedTstamp());
        self::assertInstanceOf(\RKW\RkwEvents\Domain\Model\EventReservation::class, $surveyRequestDb->getProcess());

        /** @var \TYPO3\CMS\Extbase\DomainObject\AbstractEntity $process */
        $process = $surveyRequestDb->getProcess();
        self::assertSame($process->getEvent(), $surveyRequestDb->getProcessSubject());

    }




    /**
     * @throws \Nimut\TestingFramework\Exception\Exception
     */
    public function processPendingSurveyRequestDoesNotMarkProcessedSurveyRequestAsNotifiedIfEventHasNotEndedYet()
    {

        // @todo: Theoretisch könnten die Felder shipped_tstamp und target_group auch in der
        // für die Tabelle tx_rkwshop_domain_model_order in der rkw_outcome angelegt werden?

        //  CommandController->processPendingSurveyRequestsCommand
        //  findAllPendingSurveyRequests
        //  @todo finde alle SurveyRequests im passenden Zeitraum
        //  @todo filtere alle Formate mit zugeordneter Umfrage
        //  @todo filtere auf passende Zielgruppe
        //  @todo checke den shippedTstamp
        //  @todo gruppiere nach Format
        //  @todo ggfs. zufällige Auswahl aus der Gruppierung nach Format
        //  foreach found PendingSurveyRequest
        //  processSurveyRequest($surveyRequest)->returns true/false
        //  sendNotification()->returns true/false
        //  setNotifiedTstamp(time())
        //  setProcessSubject(product, event)
        //  endforeach

        /**
         * Scenario:
         *
         * @todo Given the surveyWaitingTime is set to 172800 (2 days)
         * Given a persisted event
         * Given a persisted surveyConfiguration-object
         * Given the surveyConfiguration-property is set to that event-object
         * Given a persisted eventReservation-object
         * Given the event-property of that eventReservation-object is set to the same event-object
         * Given a persisted surveyRequest-object
         * @todo: Check targetGroup
         * Given the surveyRequest-property process is set to that eventReservation-object
         * @todo Given the order-property shippedTstamp is set to now - surveyWaitingTime
         * When the method is called
         * Then the surveyRequest-property notifiedTstamp is set to 0
         * Then the surveyRequest-property processSubject is set to null
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check80.xml');

        //  workaround - add order as Order-Object to SurveyRequest, as it is not working via Fixture due to process = AbstractEntity
        $this->setUpSurveyRequest('\RKW\RkwEvents\Domain\Model\EventReservation');

        $notifiedSurveyRequests = $this->subject->processPendingSurveyRequests(
            $checkPeriod = $this->checkPeriod,
            $maxSurveysPerPeriodAndFrontendUser = $this->maxSurveysPerPeriodAndFrontendUser,
            $surveyWaitingTime = 0
        );
        self::assertCount(0, $notifiedSurveyRequests);

        /** @var \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequestDb */
        $surveyRequestDb = $this->surveyRequestRepository->findByUid(1);
        self::assertSame(0, $surveyRequestDb->getNotifiedTstamp());
        self::assertSame(null, $surveyRequestDb->getProcessSubject());

    }


    /**
     * TearDown
     */
    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     *
     * @param string $model
     * @param int $modelUid
     *
     * @return void
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     */
    protected function setUpSurveyRequest(string $model, int $modelUid = 1): void
    {
        /** @var \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequest */
        $surveyRequest = GeneralUtility::makeInstance(SurveyRequest::class);

        $frontendUser = null;

        if ($model === '\RKW\RkwShop\Domain\Model\Order') {
            $process = $this->orderRepository->findByUid($modelUid);
            $frontendUser = $process->getFrontendUser();
            $surveyRequest->setOrder($process);
        }

        if ($model === '\RKW\RkwEvents\Domain\Model\EventReservation') {
            $process = $this->eventReservationRepository->findByUid($modelUid);
            $frontendUser = $process->getFeUser();
        }

        $surveyRequest->setProcessType(get_class($process));
        $surveyRequest->setFrontendUser($frontendUser);

        $process->getTargetGroup()->rewind();
        $surveyRequest->addTargetGroup($process->getTargetGroup()->current());

        $this->surveyRequestRepository->add($surveyRequest);
        $this->persistenceManager->persistAll();

    }

}

