<?php
namespace RKW\RkwOutcome\Command;

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

use RKW\RkwOutcome\SurveyRequest\SurveyRequestCreator;
use RKW\RkwShop\Domain\Model\Order;
use RKW\RkwShop\Domain\Repository\CategoryRepository;
use RKW\RkwShop\Domain\Repository\OrderRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

/**
 * Class CreateMissingSurveyRequestsCommand
 *
 * Execute on CLI with: 'vendor/bin/typo3 rkw_outcome:request'
 *
 * @author Christian Dilger <c.dilger@addorange.de>
 * @copyright Rkw Kompetenzzentrum
 * @package RKW_RkwOutcome
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class CreateMissingSurveyRequestsCommand extends Command
{

    /**
     * @var \RKW\RkwShop\Domain\Repository\OrderRepository|null
     */
    private ?OrderRepository $orderRepository = null;


    /**
     * @var \RKW\RkwShop\Domain\Repository\CategoryRepository|null
     */
    private ?CategoryRepository $categoryRepository;


    /**
     * @var \RKW\RkwOutcome\SurveyRequest\SurveyRequestCreator |null
     */
    protected ?SurveyRequestCreator $surveyRequestCreator = null;


    /**
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected PersistenceManager $persistenceManager;


    /**
     * @var \TYPO3\CMS\Core\Log\Logger|null
     */
    protected ?Logger $logger = null;


    /**
     * Configure the command by defining the name, options and arguments
     * @todo add descriptions and adequate default values, please
     */
    protected function configure(): void
    {
        $this->setDescription('Creates potentially missing survey request.')
            ->addArgument(
                'orderUid',
                InputArgument::OPTIONAL,
                'Uid for order to be checked (Default: 0)',
                '0'
            )
            ->addArgument(
                'targetGroupUid',
                InputArgument::OPTIONAL,
                'Uid for targetGroup to be attached (Default: 0)',
                '0'
            );
    }


    /**
     * Initializes the command after the input has been bound and before the input
     * is validated.
     *
     * This is mainly useful when a lot of commands extends one main command
     * where some things need to be initialized based on the input arguments and options.
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @see \Symfony\Component\Console\Input\InputInterface::bind()
     * @see \Symfony\Component\Console\Input\InputInterface::validate()
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);

        /** @var \RKW\RkwShop\Domain\Repository\OrderRepository $orderRepository */
        $this->orderRepository = $objectManager->get(OrderRepository::class);

        /** @var \RKW\RkwShop\Domain\Repository\CategoryRepository $categoryRepository */
        $this->categoryRepository = $objectManager->get(CategoryRepository::class);

        /** @var \RKW\RkwOutcome\SurveyRequest\SurveyRequestCreator $surveyRequestCreator */
        $this->surveyRequestCreator = $objectManager->get(SurveyRequestCreator::class);

        /** @var \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager */
        $this->persistenceManager = $objectManager->get(PersistenceManager::class);
    }


    /**
     * Executes the command for showing sys_log entries
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int
     * @see \Symfony\Component\Console\Input\InputInterface::bind()
     * @see \Symfony\Component\Console\Input\InputInterface::validate()
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title($this->getDescription());
        $io->newLine();

        $orderUid = $input->getArgument('orderUid');
        $targetGroupUid = $input->getArgument('targetGroupUid');

        $result = 0;
        $surveyRequest = null;
        try {

            /** @var \RKW\RkwShop\Domain\Model\Order $order */
            $order = $this->orderRepository->findByUid($orderUid);

            if (
                $order->getFrontendUser()->getTxFeregisterConsentMarketing()
            ) {
                if (
                    $targetGroupUid
                    && count($order->getTargetGroup()) === 0
                ) {
                    $order = $this->addMissingTargetGroup($order, $targetGroupUid);
                }

                $surveyRequest = $this->surveyRequestCreator->createSurveyRequest(
                    $order,
                    $order->getFrontendUser(),
                );

            }

            if ($surveyRequest instanceof \RKW\RkwOutcome\Domain\Model\SurveyRequest) {
                $io->note('Created missing survey request.');
            } else {
                $io->note('Nothing to create.');
            }

        } catch (\Exception $e) {

            $message = sprintf('An unexpected error occurred while trying to create a survey request for order: %s',
                str_replace(array("\n", "\r"), '', $e->getMessage())
            );

            $io->error($message);
            $this->getLogger()->log(LogLevel::ERROR, $message);
            $result = 1;
        }

        $io->writeln('Done');
        return $result;

    }


    /**
     * Returns logger instance
     *
     * @return \TYPO3\CMS\Core\Log\Logger
     */
    protected function getLogger(): \TYPO3\CMS\Core\Log\Logger
    {
        if (!$this->logger instanceof \TYPO3\CMS\Core\Log\Logger) {
            $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
        }

        return $this->logger;
    }

    /**
     * @param \RKW\RkwShop\Domain\Model\Order $order
     * @param                                 $targetGroupUid
     * @return \RKW\RkwShop\Domain\Model\Order $order
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     */
    protected function addMissingTargetGroup(\RKW\RkwShop\Domain\Model\Order $order, $targetGroupUid): Order
    {
        $order->addTargetGroup($this->categoryRepository->findByUid($targetGroupUid));
        $this->orderRepository->add($order);
        $this->persistenceManager->persistAll();

        return $order;
    }
}
