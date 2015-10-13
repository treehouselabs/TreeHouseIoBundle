<?php

namespace TreeHouse\IoBundle\Bridge\WorkerBundle\EventListener;

use TreeHouse\IoBundle\Bridge\WorkerBundle\Executor\ScrapeRevisitSourceExecutor;
use TreeHouse\IoBundle\Event\SourceEvent;
use TreeHouse\WorkerBundle\QueueManager;

class ScrapeSourceListener
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
     * @param SourceEvent $event
     */
    public function onScrapeRevisitSource(SourceEvent $event)
    {
        $this->queueManager->addForObject(ScrapeRevisitSourceExecutor::NAME, $event->getSource());
    }
}
