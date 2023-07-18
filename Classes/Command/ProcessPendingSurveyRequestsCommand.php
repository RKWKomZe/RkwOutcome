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

use RKW\RkwOutcome\SurveyRequest\SurveyRequestProcessor;
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

/**
 * Class ProcessPendingSurveyRequestsCommand
 *
 * Execute on CLI with: 'vendor/bin/typo3 rkw_outcome:request'
 *
 * @author Christian Dilger <c.dilger@addorange.de>
 * @copyright Rkw Kompetenzzentrum
 * @package RKW_RkwOutcome
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ProcessPendingSurveyRequestsCommand extends Command
{

    /**
     * @var \RKW\RkwOutcome\SurveyRequest\SurveyRequestProcessor|null
     */
    protected ?SurveyRequestProcessor $surveyRequestProcessor = null;


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
        $this->setDescription('Processes all pending survey requests.')
            ->addArgument(
                'checkPeriod',
                InputArgument::OPTIONAL,
                'Period of time to be checked for maximum number of allowed notifications (default: 30 days)',
                30
            )
            ->addArgument(
                'maxSurveysPerPeriodAndFrontendUser',
                InputArgument::OPTIONAL,
                'Maximum number of notifications to send out for a given period of time (Default: 0)',
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

        /** @var \RKW\RkwOutcome\SurveyRequest\SurveyRequestProcessor $surveyRequestProcessor */
        $this->surveyRequestProcessor = $objectManager->get(SurveyRequestProcessor::class);
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

        $checkPeriod = $input->getArgument('checkPeriod') * 24 * 60 * 60;
        $maxSurveysPerPeriodAndFrontendUser= $input->getArgument('maxSurveysPerPeriodAndFrontendUser');

        $result = 0;
        try {

            $processed = count(
                $this->surveyRequestProcessor->processPendingSurveyRequests(
                    $checkPeriod,
                    $maxSurveysPerPeriodAndFrontendUser,
                )
            );

            if ($processed) {
                $io->note('Processing was successful.');
            } else {
                $io->note('Nothing to process.');
            }

        } catch (\Exception $e) {

            $message = sprintf('An unexpected error occurred while trying to process survey-requests: %s',
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
}
