<?php

namespace TreeHouse\IoBundle\Import\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use TreeHouse\Feeder\Event\FailedItemModificationEvent;
use TreeHouse\Feeder\Event\ResourceEvent;
use TreeHouse\Feeder\Event\TransportEvent;
use TreeHouse\Feeder\FeedEvents;
use TreeHouse\IoBundle\Import\Event\FailedItemEvent;
use TreeHouse\IoBundle\Import\Event\ImporterEvent;
use TreeHouse\IoBundle\Import\Event\PartEvent;
use TreeHouse\IoBundle\Import\Event\SkippedItemEvent;
use TreeHouse\IoBundle\Import\Event\SuccessItemEvent;
use TreeHouse\IoBundle\Import\Feed\TransportFactory;
use TreeHouse\IoBundle\Import\ImportEvents;

class ImportLoggingSubscriber implements EventSubscriberInterface
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents()
    {
        return [
            FeedEvents::FETCH_CACHED             => 'onFetchCached',
            FeedEvents::PRE_FETCH                => 'onPreFetch',
            FeedEvents::POST_FETCH               => 'onPostFetch',
            FeedEvents::RESOURCE_START           => 'onResourceStart',
            FeedEvents::ITEM_MODIFICATION_FAILED => 'onItemModificationFailure',
            ImportEvents::ITEM_SUCCESS           => 'onItemSuccess',
            ImportEvents::ITEM_FAILED            => 'onItemFailed',
            ImportEvents::ITEM_SKIPPED           => 'onItemSkipped',
            ImportEvents::PART_CREATED           => 'onPartCreated',
        ];
    }

    /**
     * FeedEvents::FETCH_CACHED event
     */
    public function onFetchCached()
    {
        $this->logger->info('Using cached feed');
    }

    /**
     * @param TransportEvent $event
     */
    public function onPreFetch(TransportEvent $event)
    {
        $size = $event->getTransport()->getSize();

        $this->logger->info(
            sprintf(
                'Fetching %s (%s KB)',
                (string) $event->getTransport(),
                $size > 0 ? number_format(round($size / 1024), 0, ',', '.') : 'unknown'
            )
        );
    }

    /**
     * @param TransportEvent $event
     */
    public function onPostFetch(TransportEvent $event)
    {
        $this->logger->info(sprintf('Saved to %s', $event->getTransport()->getDestination()));
    }

    /**
     * @param ResourceEvent $event
     */
    public function onResourceStart(ResourceEvent $event)
    {
        $this->logger->debug(sprintf(
            'Processing resource %s (%d resources left)',
            (string) $event->getResource()->getTransport(),
            $event->getResources()->count()
        ));
    }

    /**
     * @param FailedItemModificationEvent $event
     */
    public function onItemModificationFailure(FailedItemModificationEvent $event)
    {
        $this->logger->warning($event->getException()->getMessage());
    }

    /**
     * @param SuccessItemEvent $event
     */
    public function onItemSuccess(SuccessItemEvent $event)
    {
        $char = '✎';
        $result = 'modified';

        if (!$event->getResult()->getId()) {
            $char = '✚';
            $result = 'added';
        }

        $result = str_pad($result, 9, ' ', STR_PAD_LEFT);

        $this->logger->info(sprintf('%s %s: %s', $char, $result, (string) $event->getItem()));
    }

    /**
     * @param SkippedItemEvent $event
     */
    public function onItemSkipped(SkippedItemEvent $event)
    {
        $this->logger->notice(sprintf('# skipped: %s', (string) $event->getItem()));
        $this->logger->notice(sprintf('   reason: %s', $event->getReason()));
    }

    /**
     * @param FailedItemEvent $event
     */
    public function onItemFailed(FailedItemEvent $event)
    {
        $this->logger->notice(sprintf('✘  failed: %s', (string) $event->getItem()));
        $this->logger->notice(sprintf('   reason: %s', $event->getReason()));
    }

    /**
     * @param PartEvent $event
     */
    public function onPartCreated(PartEvent $event)
    {
        $part = $event->getPart();

        $this->logger->debug(
            sprintf(
                '=> %d: %s',
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

        $this->logger->info(sprintf('Import ended in %s seconds', round($result->getElapsedTime())));
        $this->logger->info(
            sprintf(
                'Processed <info>%s</info> of <info>%s</info> items (<info>%d%%</info>):',
                $result->getProcessed(),
                $result->getTotal(),
                $result->getProcessed() / $result->getTotal() * 100
            )
        );
        $this->logger->info(sprintf('- succes:  <info>%s</info>', $result->getSuccess()));
        $this->logger->info(sprintf('- failed:  <info>%s</info>', $result->getFailed()));
        $this->logger->info(sprintf('- skipped: <info>%s</info>', $result->getSkipped()));
    }
}
