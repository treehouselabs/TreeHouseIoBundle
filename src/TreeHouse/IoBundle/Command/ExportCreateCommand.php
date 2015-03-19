<?php

namespace TreeHouse\IoBundle\Command;

use PK\CommandExtraBundle\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use TreeHouse\IoBundle\Event\ExportFeedEvent;
use TreeHouse\IoBundle\Export\ExportEvents;
use TreeHouse\IoBundle\Export\FeedExporter;
use TreeHouse\IoBundle\Export\FeedType\FeedTypeInterface;

class ExportCreateCommand extends Command
{
    /**
     * @var FeedExporter
     */
    protected $exporter;

    /**
     * @param FeedExporter $exporter
     */
    public function __construct(FeedExporter $exporter)
    {
        parent::__construct();

        $this->exporter = $exporter;
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('io:export:create');
        $this->addArgument('type', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 'The type(s) to export feeds for. If left empty, feeds for all known types are exported.');
        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Whether to force generating of the export and ignore cached versions');

        $this->isSingleProcessed();
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $types = $this->getTypes($input->getArgument('type'));

        $progress = new ProgressBar($output);
        $progress->setFormat('verbose');

        $this->setupProgressListeners($this->exporter->getDispatcher(), $progress, $output);

        foreach ($types as $type) {
            $this->exporter->exportFeed($type, $input->getOption('force'));
        }
    }

    /**
     * @param EventDispatcherInterface $dispatcher
     * @param ProgressBar              $progress
     * @param OutputInterface          $output
     */
    protected function setupProgressListeners(EventDispatcherInterface $dispatcher, ProgressBar $progress, OutputInterface $output)
    {
        $dispatcher->addListener(
            ExportEvents::PRE_EXPORT_FEED,
            function (ExportFeedEvent $event) use ($progress, $output) {
                $output->writeln(
                    sprintf(
                        'Exporting feed for <info>%s</info> to <info>%s</info>',
                        $event->getType()->getName(),
                        $event->getFile()
                    )
                );

                $progress->start($event->getTotal());
            }
        );

        $dispatcher->addListener(
            ExportEvents::POST_EXPORT_ITEM,
            function () use ($progress) {
                $progress->advance(1);
            }
        );

        $dispatcher->addListener(
            ExportEvents::POST_EXPORT_FEED,
            function () use ($progress) {
                $progress->finish();
            }
        );
    }

    /**
     * @param array $types
     *
     * @return FeedTypeInterface[]
     */
    protected function getTypes(array $types)
    {
        if (empty($types)) {
            return $this->exporter->getTypes();
        }

        $result = [];
        foreach ($types as &$type) {
            $result[] = $this->exporter->getType($type);
        }

        return $result;
    }
}
