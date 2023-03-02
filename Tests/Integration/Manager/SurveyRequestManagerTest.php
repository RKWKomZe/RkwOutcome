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
use RKW\RkwOutcome\Domain\Model\SurveyRequest;
use RKW\RkwOutcome\Domain\Repository\SurveyRequestRepository;
use RKW\RkwOutcome\Manager\SurveyRequestManager;
use RKW\RkwShop\Domain\Repository\FrontendUserRepository;
use RKW\RkwShop\Domain\Repository\OrderRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

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
        'typo3conf/ext/rkw_outcome',
        'typo3conf/ext/rkw_registration',
        'typo3conf/ext/rkw_shop',
        'typo3conf/ext/rkw_survey',
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
     * @var \RKW\RkwShop\Domain\Repository\FrontendUserRepository
     */
    private $frontendUserRepository = null;


    /**
     * @var \RKW\RkwShop\Domain\Repository\OrderRepository
     */
    private $orderRepository = null;


    /**
     * @var \RKW\RkwBasics\Domain\Repository\TargetGroupRepository
     */
    private $targetGroupRepository = null;


    /**
     * @var \RKW\RkwOutcome\Domain\Repository\SurveyRequestRepository
     */
    private $surveyRequestRepository = null;


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
        $this->frontendUserRepository = $this->objectManager->get(FrontendUserRepository::class);
        $this->orderRepository = $this->objectManager->get(OrderRepository::class);
        $this->targetGroupRepository = $this->objectManager->get(TargetGroupRepository::class);
        $this->surveyRequestRepository = $this->objectManager->get(SurveyRequestRepository::class);
        $this->subject = $this->objectManager->get(SurveyRequestManager::class);

    }


    /**
     * @test
     * @throws \Exception
     */
    public function createSurveyRequestCreatesSurveyRequest()
    {

        /**
         * Scenario:
         *
         * Given an order-object that is persisted
         * Given an orderItem-object that is persisted and belongs to that order-object
         * Given a product-object is persisted and is contained within that orderItem-object
         * When the method is called
         * Then an instance of \RKW\RkwOutcome\Model\SurveyRequest is returned
         * Then the order-property of this instance is set to the order-object
         * Then the frontendUser-property of this instance is set to the frontendUser-object
         * Then the targetGroup-property of this instance is set to targetGroup-property of the order-object
         * Then the surveyRequest-object is persisted
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check10.xml');

        /** @var \RKW\RkwShop\Domain\Model\Order $order */
        $order = $this->orderRepository->findByUid(1);

        /** @var \RKW\RkwShop\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByUid(1);

        /** @var \RKW\RkwBasics\Domain\Model\TargetGroup $targetGroup */
        $targetGroup = $this->targetGroupRepository->findByUid(1);

        //  @todo: Darf ein per SignalSlot angesprochene Methode überhaupt etwas zurückliefern?
        /** @var \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequest */
        $surveyRequest = $this->subject->createSurveyRequest($order);

        self::assertInstanceOf(SurveyRequest::class, $surveyRequest);
        self::assertEquals($order, $surveyRequest->getProcess());
        self::assertEquals(get_class($order), $surveyRequest->getProcessType());
        self::assertEquals($frontendUser, $surveyRequest->getFrontendUser());
        self::assertEquals($targetGroup, $surveyRequest->getTargetGroup());

        /** @var  \TYPO3\CMS\Extbase\Persistence\QueryResultInterface $surveyRequests */
        $surveyRequestsDb = $this->surveyRequestRepository->findAll();
        self::assertCount(1, $surveyRequestsDb);

        /** @var \RKW\RkwOutcome\Domain\Model\SurveyRequest $surveyRequest */
        $surveyRequestDb = $surveyRequestsDb->getFirst();
        self::assertEquals($surveyRequest, $surveyRequestDb);

    }

    /**
     * TearDown
     */
    protected function tearDown(): void
    {
        parent::tearDown();
    }

}