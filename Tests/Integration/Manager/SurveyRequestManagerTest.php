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

use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use RKW\RkwBasics\Domain\Repository\TargetGroupRepository;
use RKW\RkwEvents\Domain\Model\EventReservation;
use RKW\RkwEvents\Domain\Repository\EventRepository;
use RKW\RkwEvents\Domain\Repository\EventReservationRepository;
use RKW\RkwOutcome\Domain\Model\SurveyRequest;
use RKW\RkwOutcome\Domain\Repository\SurveyConfigurationRepository;
use RKW\RkwOutcome\Domain\Repository\SurveyRequestRepository;
use RKW\RkwOutcome\Manager\SurveyRequestManager;
use RKW\RkwShop\Domain\Repository\OrderRepository;
use RKW\RkwShop\Domain\Repository\ProductRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
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
    private $subject = null;


    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
     */
    private $objectManager = null;


    /**
     * @var \RKW\RkwShop\Domain\Repository\FrontendUserRepository|\RKW\RkwEvents\Domain\Repository\FrontendUserRepository
     */
    private $frontendUserRepository = null;


    /**
     * @var \RKW\RkwEvents\Domain\Repository\EventReservationRepository
     */
    private $eventReservationRepository = null;


    /**
     * @var \RKW\RkwShop\Domain\Repository\OrderRepository
     */
    private $orderRepository = null;


    /**
     * @var \RKW\RkwShop\Domain\Repository\OrderRepository|\RKW\RkwEvents\Domain\Repository\EventReservationRepository
     */
    private $processRepository = null;


    /**
     * @var \RKW\RkwShop\Domain\Repository\ProductRepository
     */
    private $productRepository = null;


    /**
     * @var \RKW\RkwBasics\Domain\Repository\TargetGroupRepository
     */
    private $targetGroupRepository = null;


    /**
     * @var \RKW\RkwOutcome\Domain\Repository\SurveyRequestRepository
     */
    private $surveyRequestRepository = null;


    /**
     * @var \RKW\RkwOutcome\Domain\Repository\SurveyConfigurationRepository
     */
    private $surveyConfigurationRepository = null;


    /**
     * PersistenceManager
     *
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager
     */
    protected $persistenceManager;


    /**
     * Setup
     * @throws \Exception
     */
    protected function setUp()
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
        $this->persistenceManager = GeneralUtility::makeInstance(PersistenceManager::class);
        $this->orderRepository = $this->objectManager->get(OrderRepository::class);
        $this->eventReservationRepository = $this->objectManager->get(EventReservationRepository::class);
        $this->targetGroupRepository = $this->objectManager->get(TargetGroupRepository::class);
        $this->surveyRequestRepository = $this->objectManager->get(SurveyRequestRepository::class);
        $this->surveyConfigurationRepository = $this->objectManager->get(SurveyConfigurationRepository::class);
        $this->productRepository = $this->objectManager->get(ProductRepository::class);
        $this->subject = $this->objectManager->get(SurveyRequestManager::class);

    }


    /**
     * @test
     * @throws \Exception
     */
    public function createSurveyRequestCreatesSurveyRequestTriggeredByAnOrder()
    {

        /**
         * Scenario:
         *
         * Given an order-object that is persisted
         * Given an orderItem-object that is persisted and belongs to that order-object
         * Given a product-object is persisted and is contained within that orderItem-object
         * Given a surveyConfiguration is associated with product-object
         * Given the surveyConfiguration-property targetGroup is set to the order-property targetGroup
         * When the method is called
         * Then an instance of \RKW\RkwOutcome\Model\SurveyRequest is returned
         * Then the order-property of this instance is set to the order-object
         * Then the frontendUser-property of this instance is set to the frontendUser-object
         * Then the targetGroup-property of this instance is set to targetGroup-property of the order-object
         * Then the surveyRequest-object is persisted
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check10.xml');

        /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $this->objectManager */
        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->frontendUserRepository = $this->objectManager->get(\RKW\RkwShop\Domain\Repository\FrontendUserRepository::class);
        $this->processRepository = $this->objectManager->get(\RKW\RkwShop\Domain\Repository\OrderRepository::class);

        /** @var \RKW\RkwShop\Domain\Model\Order $process */
        $process = $this->processRepository->findByUid(1);

        /** @var \RKW\RkwShop\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByUid(1);

        /** @var \RKW\RkwBasics\Domain\Model\TargetGroup $targetGroup */
        $targetGroup = $this->targetGroupRepository->findByUid(1);

        /** @var \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequest */
        $surveyRequest = $this->subject->createSurveyRequest($frontendUser, $process);

        self::assertInstanceOf(SurveyRequest::class, $surveyRequest);
        self::assertEquals($process, $surveyRequest->getProcess());
        self::assertEquals(get_class($process), $surveyRequest->getProcessType());
        self::assertEquals($frontendUser, $surveyRequest->getFrontendUser());
        self::assertInstanceOf(\RKW\RkwRegistration\Domain\Model\FrontendUser::class, $surveyRequest->getFrontendUser());
        self::assertEquals($targetGroup, $surveyRequest->getTargetGroup());

        /** @var  \TYPO3\CMS\Extbase\Persistence\QueryResultInterface $surveyRequests */
        $surveyRequestsDb = $this->surveyRequestRepository->findAll();
        self::assertCount(1, $surveyRequestsDb);

        /** @var \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequest */
        $surveyRequestDb = $surveyRequestsDb->getFirst();
        self::assertEquals($surveyRequest, $surveyRequestDb);
        self::assertInstanceOf(\RKW\RkwShop\Domain\Model\Order::class, $surveyRequestDb->getProcess());

    }


    /**
     * @test
     * @throws \Exception
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
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check20.xml');

        /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $this->objectManager */
        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->frontendUserRepository = $this->objectManager->get(\RKW\RkwEvents\Domain\Repository\FrontendUserRepository::class);
        $this->processRepository = $this->objectManager->get(\RKW\RkwEvents\Domain\Repository\EventReservationRepository::class);

        /** @var \RKW\RkwEvents\Domain\Model\EventReservation $process */
        $process = $this->processRepository->findByUid(1);

        /** @var \RKW\RkwEvents\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByUid(1);

        /** @var \RKW\RkwBasics\Domain\Model\TargetGroup $targetGroup */
        $targetGroup = $this->targetGroupRepository->findByUid(1);

        //  @todo: Darf ein per SignalSlot angesprochene Methode überhaupt etwas zurückliefern?
        /** @var \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequest */
        $surveyRequest = $this->subject->createSurveyRequest($frontendUser, $process);

        self::assertInstanceOf(SurveyRequest::class, $surveyRequest);
        self::assertEquals($process, $surveyRequest->getProcess());
        self::assertEquals(get_class($process), $surveyRequest->getProcessType());
        self::assertEquals($frontendUser, $surveyRequest->getFrontendUser());
        self::assertInstanceOf(\RKW\RkwRegistration\Domain\Model\FrontendUser::class, $surveyRequest->getFrontendUser());
        self::assertEquals($targetGroup, $surveyRequest->getTargetGroup());

        /** @var  \TYPO3\CMS\Extbase\Persistence\QueryResultInterface $surveyRequests */
        $surveyRequestsDb = $this->surveyRequestRepository->findAll();
        self::assertCount(1, $surveyRequestsDb);

        /** @var \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequest */
        $surveyRequestDb = $surveyRequestsDb->getFirst();
        self::assertEquals($surveyRequest, $surveyRequestDb);
        self::assertInstanceOf(EventReservation::class, $surveyRequestDb->getProcess());

    }


    //  @todo: Check on Marketing-Häkchen!!! Wo setzen wir das überhaupt?
    //  @todo: Check, if existing survey is connected to same target group
    //  @todo: Check, if connected survey is due (in between starttime <> endtime)

    /**
     * @test
     * @throws \Exception
     */
    public function createSurveyRequestTriggeredByAnOrderDoesNotCreateSurveyRequestIfNoSurveyIsAssociatedWithContainedProduct()
    {

        /**
         * Scenario:
         *
         * Given an order-object that is persisted
         * Given an orderItem-object that is persisted and belongs to that order-object
         * Given a product-object is persisted and is contained within that orderItem-object
         * Given no survey is associated with product-object
         * When the method is called
         * Then null is returned
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check30.xml');

        /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $this->objectManager */
        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->frontendUserRepository = $this->objectManager->get(\RKW\RkwShop\Domain\Repository\FrontendUserRepository::class);
        $this->processRepository = $this->objectManager->get(\RKW\RkwShop\Domain\Repository\OrderRepository::class);

        /** @var \RKW\RkwShop\Domain\Model\Order $process */
        $process = $this->processRepository->findByUid(1);

        /** @var \RKW\RkwShop\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByUid(1);

        /** @var \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequest */
        $surveyRequest = $this->subject->createSurveyRequest($frontendUser, $process);
        self::assertNull($surveyRequest);

        /** @var  \TYPO3\CMS\Extbase\Persistence\QueryResultInterface $surveyRequests */
        $surveyRequestsDb = $this->surveyRequestRepository->findAll();
        self::assertCount(0, $surveyRequestsDb);

    }

    /**
     * @test
     * @throws \Exception
     */
    public function createSurveyRequestTriggeredByAnEventDoesNotCreateSurveyRequestIfNoSurveyIsAssociatedWithContainedEvent()
    {

    }

    /**
     * @test
     * @throws \Exception
     */
    public function createSurveyRequestTriggeredByAnOrderDoesNotCreateSurveyRequestIfAssociatedSurveyDoesNotUseSameTargetgroupAsOrder()
    {

        /**
         * Scenario:
         *
         * Given an order-object that is persisted
         * Given the targetGroup-property of this instance is set to uid 1
         * Given an orderItem-object that is persisted and belongs to that order-object
         * Given a product-object is persisted and is contained within that orderItem-object
         * Given a survey is associated with product-object
         * Given the targetGroup-property of this associated survey is set to uid 2
         * When the method is called
         * Then null is returned
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check40.xml');

        /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $this->objectManager */
        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->frontendUserRepository = $this->objectManager->get(\RKW\RkwShop\Domain\Repository\FrontendUserRepository::class);
        $this->processRepository = $this->objectManager->get(\RKW\RkwShop\Domain\Repository\OrderRepository::class);

        /** @var \RKW\RkwShop\Domain\Model\Order $process */
        $process = $this->processRepository->findByUid(1);

        /** @var \RKW\RkwShop\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByUid(1);

        /** @var \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequest */
        $surveyRequest = $this->subject->createSurveyRequest($frontendUser, $process);
        self::assertNull($surveyRequest);

        /** @var  \TYPO3\CMS\Extbase\Persistence\QueryResultInterface $surveyRequests */
        $surveyRequestsDb = $this->surveyRequestRepository->findAll();
        self::assertCount(0, $surveyRequestsDb);

    }


    /**
     * @test
     * @throws \Exception
     */
    public function createSurveyRequestTriggeredByAnEventDoesNotCreateSurveyRequestIfAssociatedSurveyDoesNotUseSameTargetgroupAsEventReservation()
    {


    }


    /**
     * @test
     * @throws \Exception
     */
    public function processPendingSurveyRequestMarksProcessedSurveyRequestAsNotifiedIfSurveyRequestContainsAnOrder()
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
         * Given a persisted product
         * Given a persisted surveyConfiguration-object
         * Given the surveyConfiguration-property is set to that product-object
         * Given a persisted order-object
         * Given the product-property ot the contained orderItem-object ist set to the same product-object
         * Given a persisted surveyRequest-object
         * @todo: Check targetGroup
         * Given the surveyRequest-property process is set to that order
         * @todo Given the order-property shippedTstamp is set to now - surveyWaitingTime
         * When the method is called
         * Then the surveyRequest-property notifiedTstamp is set to > 0
         * Then the surveyRequest-property processSubject is set to that product-object
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check50.xml');

        //  workaround - add order as Order-Object to SurveyRequest, as it is not working via Fixture due to process = AbstractEntity
        $processDb = $this->setUpSurveyRequest('\RKW\RkwShop\Domain\Model\Order');

        $notifiedSurveyRequests = $this->subject->processPendingSurveyRequests();
        self::assertCount(1, $notifiedSurveyRequests);

        /** @var \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequestDb */
        $surveyRequestDb = $this->surveyRequestRepository->findByUid(1);
        self::assertGreaterThan(0, $surveyRequestDb->getNotifiedTstamp());

        $processDb->getOrderItem()->rewind();
        self::assertEquals($processDb->getOrderItem()->current()->getProduct(), $surveyRequestDb->getProcessSubject());

    }


    /**
     * @test
     * @throws \Exception
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
         * Given the event-property of that eventReservation-object is set to the that event-object
         * Given a persisted surveyRequest-object
         * @todo: Check targetGroup
         * Given the surveyRequest-property process is set to that eventReservation-object
         * @todo Given the order-property shippedTstamp is set to now - surveyWaitingTime
         * When the method is called
         * Then the surveyRequest-property notifiedTstamp is set to > 0
         * Then the surveyRequest-property processSubject is set to that eventReservation-object
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check60.xml');

        //  workaround - add order as Order-Object to SurveyRequest, as it is not working via Fixture due to process = AbstractEntity
        $processDb = $this->setUpSurveyRequest('\RKW\RkwEvents\Domain\Model\EventReservation');

        $notifiedSurveyRequests = $this->subject->processPendingSurveyRequests();
        self::assertCount(1, $notifiedSurveyRequests);

        /** @var \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequestDb */
        $surveyRequestDb = $this->surveyRequestRepository->findByUid(1);
        self::assertGreaterThan(0, $surveyRequestDb->getNotifiedTstamp());
        self::assertEquals($processDb->getEvent(), $surveyRequestDb->getProcessSubject());

    }


    /**
     * @test
     * @throws \Exception
     */
    public function processPendingSurveyRequestDoesNotMarkProcessedSurveyRequestAsNotifiedIfOrderHasNotBeenShippedYet()
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
         * Given a persisted product
         * Given a persisted surveyConfiguration-object
         * Given the surveyConfiguration-property is set to that product-object
         * Given a persisted order-object
         * Given the product-property ot the contained orderItem-object ist set to the same product-object
         * Given a persisted surveyRequest-object
         * @todo: Check targetGroup
         * Given the surveyRequest-property process is set to that order
         * @todo Given the order-property shippedTstamp is set to now - surveyWaitingTime
         * When the method is called
         * Then the surveyRequest-property notifiedTstamp is set to 0
         * Then the surveyRequest-property processSubject is set to null
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check70.xml');

        //  workaround - add order as Order-Object to SurveyRequest, as it is not working via Fixture due to process = AbstractEntity
        $processDb = $this->setUpSurveyRequest('\RKW\RkwShop\Domain\Model\Order');

        $notifiedSurveyRequests = $this->subject->processPendingSurveyRequests();
        self::assertCount(0, $notifiedSurveyRequests);

        /** @var \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequestDb */
        $surveyRequestDb = $this->surveyRequestRepository->findByUid(1);
        self::assertEquals(0, $surveyRequestDb->getNotifiedTstamp());
        self::assertEquals(null, $surveyRequestDb->getProcessSubject());

    }


    /**
     * @test
     * @throws \Exception
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
        $processDb = $this->setUpSurveyRequest('\RKW\RkwEvents\Domain\Model\EventReservation');

        $notifiedSurveyRequests = $this->subject->processPendingSurveyRequests();
        self::assertCount(0, $notifiedSurveyRequests);

        /** @var \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequestDb */
        $surveyRequestDb = $this->surveyRequestRepository->findByUid(1);
        self::assertEquals(0, $surveyRequestDb->getNotifiedTstamp());
        self::assertEquals(null, $surveyRequestDb->getProcessSubject());

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
     *
     * @return \TYPO3\CMS\Extbase\DomainObject\AbstractEntity $process
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     */
    protected function setUpSurveyRequest(string $model): \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
    {
        /** @var \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequest */
        $surveyRequest = GeneralUtility::makeInstance(SurveyRequest::class);

        if ($model === '\RKW\RkwShop\Domain\Model\Order') {
            $process = $this->orderRepository->findByUid(1);
            $frontendUser = $process->getFrontendUser();
        }

        if ($model === '\RKW\RkwEvents\Domain\Model\EventReservation') {
            $process = $this->eventReservationRepository->findByUid(1);
            $frontendUser = $process->getFeUser();
        }

        $surveyRequest->setProcess($process);
        $surveyRequest->setProcessType(get_class($process));
        $surveyRequest->setFrontendUser($frontendUser);
        $surveyRequest->setTargetGroup($process->getTargetGroup());
        $this->surveyRequestRepository->add($surveyRequest);
        $this->persistenceManager->persistAll();

        /** @var \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequestDb */
        $surveyRequestDb = $this->surveyRequestRepository->findByUid(1);
        self::assertEquals($process, $surveyRequestDb->getProcess());
        self::assertInstanceOf($model, $surveyRequestDb->getProcess());


//        $processDb = $this->orderRepository->findByUid(1);
//
//        /** @var \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequest */
//        $surveyRequest = GeneralUtility::makeInstance(SurveyRequest::class);
//        $surveyRequest->setProcess($processDb);
//        $surveyRequest->setProcessType(get_class($processDb));
//        $surveyRequest->setFrontendUser($processDb->getFrontendUser()); //  @todo: Entweder direkt oder per $process->getFrontendUser()
//        $surveyRequest->setTargetGroup($processDb->getTargetGroup());
//        $this->surveyRequestRepository->add($surveyRequest);
//        $this->persistenceManager->persistAll();
//
//        /** @var \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequestDb */
//        $surveyRequestDb = $this->surveyRequestRepository->findByUid(1);
//        self::assertEquals($processDb, $surveyRequestDb->getProcess());
//        self::assertInstanceOf(\RKW\RkwShop\Domain\Model\Order::class, $surveyRequestDb->getProcess());


        return $process;
    }

}