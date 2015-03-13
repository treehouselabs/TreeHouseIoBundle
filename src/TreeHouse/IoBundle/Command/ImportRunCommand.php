<?php

namespace TreeHouse\IoBundle\Command;

use Doctrine\Common\Persistence\ManagerRegistry;
use PK\CommandExtraBundle\Command\Command;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use TreeHouse\IoBundle\Entity\Feed;
use TreeHouse\IoBundle\Entity\FeedRepository;
use TreeHouse\IoBundle\EventListener\ImportOutputSubscriber;
use TreeHouse\IoBundle\Import\ImportFactory;

class ImportRunCommand extends Command
{
    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    /**
     * @var ImportFactory
     */
    protected $importFactory;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param ManagerRegistry $doctrine
     * @param ImportFactory   $importFactory
     * @param LoggerInterface $logger
     */
    public function __construct(ManagerRegistry $doctrine, ImportFactory $importFactory, LoggerInterface $logger)
    {
        $this->doctrine      = $doctrine;
        $this->importFactory = $importFactory;
        $this->logger        = $logger;

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('io:import:run');
        $this->addArgument('id', InputArgument::IS_ARRAY, 'Specify which feeds to import, defaults to all');
        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Force import of all items, skipping modification date checks');
        $this->setDescription('Runs a new import');
        $this->isSingleProcessed();
        $this->setSummarizeDefinition(['time' => true, 'memory' => true]);
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $ids = (array) $input->getArgument('id');

        if (empty($ids)) {
            $feeds = $this->getFeedRepository()->findAll();
        } else {
            $feeds = $this->getFeedRepository()->findBy(['id' => $ids]);
        }

        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_NORMAL) {
            $dispatcher = $this->importFactory->getEventDispatcher();
            $dispatcher->addSubscriber(new ImportOutputSubscriber($output));
        }

        $force = $input->getOption('force');

        if (empty($feeds)) {
            $output->writeln('No feeds to import');

            return 1;
        }

        foreach ($feeds as $feed) {
            if ($input->isInteractive()) {
                $this->checkForUnfinishedImports($feed, $input, $output);
            }

            $this->runImport($output, $feed, $force);
        }

        return 0;
    }

    /**
     * @param OutputInterface $output
     * @param Feed            $feed
     * @param boolean         $force
     */
    protected function runImport(OutputInterface $output, Feed $feed, $force = false)
    {
        $output->writeln(
            sprintf(
                'Starting a new import for <info>%s</info> feed <info>%d</info>',
                $feed->getOrigin()->getName(),
                $feed->getId()
            )
        );

        $import = $this->importFactory->createImport($feed, new \DateTime(), $force);
        $output->writeln(sprintf('Created import <info>%d</info>', $import->getId()));

        foreach ($import->getParts() as $part) {
            $output->writeln(sprintf('Importing part <comment>%d</comment>', $part->getPosition()));

            $job = $this->importFactory->createImportJob($part);
            $job->setLogger($this->logger);
            $job->run();
        }
    }

    /**
     * Checks feed for unfinished imports and gives the user an option to close them first
     *
     * @param Feed            $feed
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @throws \Exception
     */
    protected function checkForUnfinishedImports(Feed $feed, InputInterface $input, OutputInterface $output)
    {
        $helper = new QuestionHelper();

        foreach ($feed->getImports() as $import) {
            if (!$import->isFinished()) {
                $msg = sprintf(
                    'Import <info>%d</info> for this feed is unfinished, close it now? [y] ',
                    $import->getId()
                );

                $question = new ConfirmationQuestion($msg);
                if ($helper->ask($input, $output, $question)) {
                    $command = 'io:import:close';
                    $closeImport = $this->getApplication()->find($command);
                    $input = new ArrayInput(['command' => $command, 'import' => $import->getId()]);
                    $closeImport->run($input, $output);
                }
            }
        }
    }

    /**
     * @return FeedRepository
     */
    protected function getFeedRepository()
    {
        return $this->doctrine->getRepository('TreeHouseIoBundle:Feed');
    }
}
