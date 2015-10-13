<?php

namespace TreeHouse\IoBundle\Bridge\WorkerBundle\EventListener;

use TreeHouse\IoBundle\Bridge\WorkerBundle\Executor\ScrapeUrlExecutor;
use TreeHouse\IoBundle\Scrape\Event\ScrapeUrlEvent;
use TreeHouse\WorkerBundle\QueueManager;

class ScrapeUrlListener
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
     * @param ScrapeUrlEvent $event
     */
    public function onScrapeNextUrl(ScrapeUrlEvent $event)
    {
        $this->queueManager->add(ScrapeUrlExecutor::NAME, [$event->getScraper()->getId(), $event->getUrl()]);
    }
}
