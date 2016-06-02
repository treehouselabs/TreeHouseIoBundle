<?php

namespace TreeHouse\IoBundle\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use TreeHouse\Feeder\Event\FailedItemModificationEvent;
use TreeHouse\Feeder\Event\ResourceEvent;
use TreeHouse\Feeder\Event\TransportEvent;
use TreeHouse\Feeder\FeedEvents;

class FeederLoggingSubscriber implements EventSubscriberInterface
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
            FeedEvents::FETCH_CACHED => 'onFetchCached',
            FeedEvents::PRE_FETCH => 'onPreFetch',
            FeedEvents::POST_FETCH => 'onPostFetch',
            FeedEvents::RESOURCE_START => 'onResourceStart',
            FeedEvents::ITEM_MODIFICATION_FAILED => 'onItemModificationFailure',
        ];
    }

    /**
     * FeedEvents::FETCH_CACHED event.
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
}
