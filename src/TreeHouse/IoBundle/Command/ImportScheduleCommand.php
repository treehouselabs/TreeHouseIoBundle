<?php

namespace TreeHouse\IoBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Filesystem\LockHandler;
use TreeHouse\IoBundle\Entity\Feed;
use TreeHouse\IoBundle\EventListener\ImportOutputSubscriber;
use TreeHouse\IoBundle\Import\ImportFactory;
use TreeHouse\IoBundle\Import\ImportScheduler;

class ImportScheduleCommand extends Command
{
    /**
     * @var ImportScheduler
     */
    protected $importScheduler;

    /**
     * @var ImportFactory
     */
    protected $importFactory;

    /**
     * @param ImportFactory   $importFactory
     * @param ImportScheduler $importScheduler
     */
    public function __construct(ImportFactory $importFactory, ImportScheduler $importScheduler)
    {
        $this->importFactory = $importFactory;
        $this->importScheduler = $importScheduler;

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('io:import:schedule');
        $this->addArgument('feed', InputArgument::IS_ARRAY, 'The feed id(s), defaults to all eligible feeds (depending on <comment>--minutes</comment>');
        $this->addOption('all', 'a', InputOption::VALUE_NONE, 'Schedule import for all feeds, negates the <comment>--minutes</comment> option');
        $this->addOption('minutes', 'm', InputOption::VALUE_OPTIONAL, 'The number of minutes to schedule imports for.', 5);
        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Make the import forced, meaning no items are skipped');
        $this->setDescription('Schedules imports for one or more feeds');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $this->getName();
        $lockHandler = new LockHandler($name);
        if (!$lockHandler->lock()) {
            $output->writeln(
                sprintf('<info>%s</info> is still running, exiting.', $name)
            );

            return 0;
        }

        $minutes = (int) $input->getOption('minutes');
        if ($minutes < 1) {
            $lockHandler->release();

            throw new \InvalidArgumentException('Minutes has to be a positive number');
        }

        if ($ids = $input->getArgument('feed')) {
            $feeds = $this->importScheduler->findByIds($ids);
        } elseif ($input->getOption('all')) {
            $feeds = $this->importScheduler->findAll();
        } else {
            $feeds = $this->importScheduler->findByTime($minutes);
        }

        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_NORMAL) {
            $dispatcher = $this->importFactory->getEventDispatcher();
            $dispatcher->addSubscriber(new ImportOutputSubscriber($output));
        }

        $force = $input->getOption('force');

        $exitCode = $this->scheduleImports($input, $output, $feeds, $minutes, $force);

        $lockHandler->release();

        return $exitCode;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @param Feed[]          $feeds
     * @param int             $minutes
     * @param bool            $force
     *
     * @return int
     */
    protected function scheduleImports(InputInterface $input, OutputInterface $output, array $feeds, $minutes, $force = false)
    {
        if (empty($feeds)) {
            $output->writeln('No feeds to schedule');

            return 0;
        }

        $num = 0;
        $factor = $minutes / sizeof($feeds);

        foreach ($feeds as $feedId => $priority) {
            $offset = round($factor * $num++);
            $date = new \DateTime(sprintf('+%d minutes', $offset));

            /** @var Feed $feed */
            $feed = $this->importScheduler->findFeed($feedId);

            if ($input->isInteractive()) {
                $this->checkForUnfinishedImports($feed, $input, $output);
            }

            $output->writeln(
                sprintf(
                    'Scheduling import for <info>%s</info> feed <info>%d</info> to run at <info>%s</info>',
                    $feed->getOrigin()->getName(),
                    $feed->getId(),
                    $date->format('Y-m-d H:i:s')
                )
            );

            try {
                $import = $this->importFactory->createImport($feed, $date, $force);
                $output->writeln(sprintf('Created import <info>%d</info>', $import->getId()));

                $output->writeln('Scheduling parts');
                foreach ($import->getParts() as $part) {
                    $this->importScheduler->schedulePart($part);
                }
            } catch (\Exception $e) {
                // we could get an exception when a new import cannot be created, for example when an existing import
                // for this feed is still running.
                $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));

                continue;
            }
        }

        return 0;
    }

    /**
     * Checks feed for unfinished imports and gives the user an option to close them first.
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
}
