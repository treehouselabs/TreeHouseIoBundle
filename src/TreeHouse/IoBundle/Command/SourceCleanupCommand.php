<?php

namespace TreeHouse\IoBundle\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use TreeHouse\IoBundle\Event\SourceEvent;
use TreeHouse\IoBundle\IoEvents;
use TreeHouse\IoBundle\Origin\OriginManagerInterface;
use TreeHouse\IoBundle\Source\Cleaner\DelegatingSourceCleaner;
use TreeHouse\IoBundle\Source\Cleaner\IdleSourceCleaner;
use TreeHouse\IoBundle\Source\Cleaner\ThresholdVoter;
use TreeHouse\IoBundle\Source\Cleaner\ThresholdVoterInterface;

class SourceCleanupCommand extends Command
{
    /**
     * @var DelegatingSourceCleaner
     */
    protected $sourceCleaner;

    /**
     * @var OriginManagerInterface
     */
    protected $originManager;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param DelegatingSourceCleaner $sourceCleaner
     * @param OriginManagerInterface  $originManager
     * @param LoggerInterface         $logger
     */
    public function __construct(DelegatingSourceCleaner $sourceCleaner, OriginManagerInterface $originManager, LoggerInterface $logger)
    {
        $this->sourceCleaner = $sourceCleaner;
        $this->originManager = $originManager;
        $this->logger        = $logger;

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName('io:source:cleanup')
            ->addOption(
                'origin',
                'o',
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Select origins to cleanup for, defaults to all'
            )
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Skip checks for remove threshold')
            ->setDescription('Cleans up database by removing idle sources.')
        ;
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $origin      = $input->getOption('origin');
        $force       = $input->getOption('force');
        $interactive = $input->isInteractive();
        $dispatcher  = $this->sourceCleaner->getEventDispatcher();

        // listen to clean event
        $dispatcher->addListener(
            IoEvents::PRE_CLEAN_SOURCE,
            function (SourceEvent $event) use ($output) {
                $source = $event->getSource();
                $output->writeln(
                    sprintf('<fg=red>- %s:%s</>', $source->getFeed(), $source->getOriginalId())
                );
            }
        );

        if ($force) {
            $voter = new ThresholdVoter(
                function () {
                    return true;
                },
                $dispatcher
            );
        } else {
            $voter = new ThresholdVoter(
                function ($count, $total, $max, $message) use ($input, $output, $interactive) {
                    $output->writeln($message);

                    // see if we can ask the user to confirm cleanup
                    $question = new ConfirmationQuestion('<question>> Clean these sources anyway? [y]</question> ');
                    $helper   = new QuestionHelper();

                    return $interactive && $helper->ask($input, $output, $question);
                },
                $dispatcher
            );
        }

        $numCleaned = $this->clean($voter, $origin);

        $output->writeln(sprintf('<info>%s</info> sources cleaned', $numCleaned));

        return 0;
    }

    /**
     * @param ThresholdVoterInterface $voter
     * @param array                   $origins
     *
     * @throws \RuntimeException
     *
     * @return integer
     */
    protected function clean(ThresholdVoterInterface $voter, array $origins = null)
    {
        if ($origins) {
            $idleCleaner = null;
            foreach ($this->sourceCleaner->getCleaners() as $cleaner) {
                if ($cleaner instanceof IdleSourceCleaner) {
                    $idleCleaner = $cleaner;
                    break;
                }
            }

            if (null === $idleCleaner) {
                throw new \RuntimeException('No IdleSourceCleaner is configured');
            }

            $repo = $this->originManager->getRepository();
            $query = $repo
                ->createQueryBuilder('o')
                ->select('o')
                ->where('o.id IN (:ids)')
                ->setParameter('ids', $origins)
                ->getQuery()
            ;

            $numCleaned = 0;
            foreach ($query->getResult() as $origin) {
                $numCleaned += $idleCleaner->cleanOrigin($this->sourceCleaner, $origin, $voter);
            }

            return $numCleaned;
        } else {
            return $this->sourceCleaner->cleanAll($voter);
        }
    }
}
