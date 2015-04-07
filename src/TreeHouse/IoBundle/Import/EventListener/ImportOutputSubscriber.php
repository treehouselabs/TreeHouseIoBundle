<?php

namespace TreeHouse\IoBundle\Import\EventListener;

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use TreeHouse\Feeder\Event\FailedItemModificationEvent;
use TreeHouse\Feeder\Event\FetchProgressEvent;
use TreeHouse\Feeder\Event\ResourceEvent;
use TreeHouse\Feeder\Event\TransportEvent;
use TreeHouse\Feeder\FeedEvents;
use TreeHouse\Feeder\Transport\ProgressAwareInterface;
use TreeHouse\IoBundle\Import\Event\ExceptionEvent;
use TreeHouse\IoBundle\Import\Event\FailedItemEvent;
use TreeHouse\IoBundle\Import\Event\ImporterEvent;
use TreeHouse\IoBundle\Import\Event\PartEvent;
use TreeHouse\IoBundle\Import\Event\SkippedItemEvent;
use TreeHouse\IoBundle\Import\Event\SuccessItemEvent;
use TreeHouse\IoBundle\Import\Feed\TransportFactory;
use TreeHouse\IoBundle\Import\ImportEvents;

class ImportOutputSubscriber implements EventSubscriberInterface
{
    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var ProgressBar
     */
    protected $progress;

    /**
     * @var boolean
     */
    protected $progressActive = false;

    /**
     * @param OutputInterface $output
     */
    public function __construct(OutputInterface $output)
    {
        $this->output   = $output;
        $this->progress = new ProgressBar($output);
    }

    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents()
    {
        return [
            FeedEvents::FETCH_CACHED             => 'onFetchCached',
            FeedEvents::FETCH_PROGRESS           => 'onFetchProgress',
            FeedEvents::PRE_FETCH                => 'onPreFetch',
            FeedEvents::POST_FETCH               => 'onPostFetch',
            FeedEvents::RESOURCE_START           => 'onResourceStart',
            FeedEvents::ITEM_MODIFICATION_FAILED => 'onItemModificationFailure',
            ImportEvents::ITEM_SUCCESS           => 'onItemSuccess',
            ImportEvents::ITEM_FAILED            => 'onItemFailed',
            ImportEvents::ITEM_SKIPPED           => 'onItemSkipped',
            ImportEvents::PART_CREATED           => 'onPartCreated',
            ImportEvents::PART_FINISH            => 'onPartFinished',
            ImportEvents::EXCEPTION              => 'onException',
        ];
    }

    /**
     * FeedEvents::FETCH_CACHED event
     */
    public function onFetchCached()
    {
        $this->output->writeln('Using cached feed');
    }

    /**
     * @param TransportEvent $event
     */
    public function onPreFetch(TransportEvent $event)
    {
        $size = $event->getTransport()->getSize();
        if ($event->getTransport() instanceof ProgressAwareInterface && $size > 0) {
            $this->progress->start($size);
            $this->progressActive = true;
        }

        $this->progress->setMessage(
            sprintf(
                'Downloading <info>%s</info> (%s KB)',
                (string) $event->getTransport(),
                $size > 0 ? number_format(round($size / 1024), 0, ',', '.') : 'unknown'
            )
        );
    }

    /**
     * @param FetchProgressEvent $event
     */
    public function onFetchProgress(FetchProgressEvent $event)
    {
        if ($this->progressActive) {
            $this->progress->setProgress($event->getBytesFetched());
        }
    }

    /**
     * @param TransportEvent $event
     */
    public function onPostFetch(TransportEvent $event)
    {
        if ($this->progressActive) {
            $this->progress->finish();
            $this->progressActive = false;
        }

        $this->progress->setMessage(sprintf('Saved to <info>%s</info>', $event->getTransport()->getDestination()));
    }

    /**
     * @param ResourceEvent $event
     */
    public function onResourceStart(ResourceEvent $event)
    {
        $this->output->writeln(sprintf(
            'Processing resource <info>%s</info> (<comment>%d</comment> resources left)',
            (string) $event->getResource()->getTransport(),
            $event->getResources()->count()
        ));
    }

    /**
     * @param FailedItemModificationEvent $event
     */
    public function onItemModificationFailure(FailedItemModificationEvent $event)
    {
        $this->output->writeln(sprintf('<error>%s</error>', $event->getException()->getMessage()));
    }

    /**
     * @param SuccessItemEvent $event
     */
    public function onItemSuccess(SuccessItemEvent $event)
    {
        $this->output->writeln(
            sprintf('<info>✎  updated</info>: <comment>%s</comment>', (string) $event->getItem())
        );
    }

    /**
     * @param SkippedItemEvent $event
     */
    public function onItemSkipped(SkippedItemEvent $event)
    {
        $this->output->writeln(
            sprintf('<options=bold>#  skipped</>: <comment>%s</comment>', (string) $event->getItem())
        );

        $this->output->writeln(sprintf('<options=bold>    reason</>: %s', $event->getReason()));
    }

    /**
     * @param FailedItemEvent $event
     */
    public function onItemFailed(FailedItemEvent $event)
    {
        $this->output->writeln(
            sprintf('<fg=red;options=bold>✘   failed</>: <comment>%s</comment>', (string) $event->getItem())
        );

        $this->output->writeln(sprintf('<fg=red;options=bold>    reason</>: %s', $event->getReason()));
    }

    /**
     * @param PartEvent $event
     */
    public function onPartCreated(PartEvent $event)
    {
        $part = $event->getPart();

        $this->output->writeln(
            sprintf(
                '=> <comment>%d: %s</comment>',
                $part->getPosition(),
                (string) TransportFactory::createTransportFromConfig($part->getTransportConfig())
            )
        );
    }

    /**
     * @param ImporterEvent $event
     */
    public function onPartFinished(ImporterEvent $event)
    {
        $importer = $event->getImporter();
        $result = $importer->getResult();

        $total      = $result->getTotal();
        $processed  = $result->getProcessed();
        $percentage = $total > 0 ? ($processed / $total * 100) : 0;

        $this->output->writeln(sprintf('Import ended in %s seconds', round($result->getElapsedTime())));
        $this->output->writeln(
            sprintf(
                'Processed <info>%s</info> of <info>%s</info> items (<info>%d%%</info>):',
                $processed,
                $total,
                $percentage
            )
        );

        $this->output->writeln(sprintf('- succes:  <info>%s</info>', $result->getSuccess()));
        $this->output->writeln(sprintf('- failed:  <info>%s</info>', $result->getFailed()));
        $this->output->writeln(sprintf('- skipped: <info>%s</info>', $result->getSkipped()));
    }

    /**
     * @param ExceptionEvent $event
     */
    public function onException(ExceptionEvent $event)
    {
        $this->output->writeln(sprintf('<error>%s</error>', $event->getException()->getMessage()));
    }
}
