<?php

namespace TreeHouse\IoBundle\Bridge\WorkerBundle\EventListener;

use FM\WorkerBundle\QueueManager;
use TreeHouse\IoBundle\Bridge\WorkerBundle\Executor\ScrapeRevisitSourceExecutor;
use TreeHouse\IoBundle\Event\SourceEvent;

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
