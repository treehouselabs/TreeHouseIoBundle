<?php

namespace TreeHouse\IoBundle\Bridge\WorkerBundle\EventListener;

use TreeHouse\WorkerBundle\QueueManager;
use TreeHouse\IoBundle\Bridge\WorkerBundle\Executor\ScrapeUrlExecutor;
use TreeHouse\IoBundle\Scrape\Event\RateLimitEvent;

class ScrapeRateLimitListener
{
    /**
     * @var QueueManager
     */
    protected $queueManager;

    /**
     * @param QueueManager $queueManager
     */
    public function __construct(QueueManager $queueManager)
    {
        $this->queueManager = $queueManager;
    }

    /**
     * @param RateLimitEvent $event
     */
    public function onRateLimit(RateLimitEvent $event)
    {
        $payload = [$event->getScraper()->getId(), $event->getUrl()];

        $this->queueManager->add(ScrapeUrlExecutor::NAME, $payload, $event->getRetryDate()->getTimestamp() - time());
    }
}
