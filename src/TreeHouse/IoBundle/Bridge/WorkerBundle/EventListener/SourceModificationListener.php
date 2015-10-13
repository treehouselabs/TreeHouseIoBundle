<?php

namespace TreeHouse\IoBundle\Bridge\WorkerBundle\EventListener;

use TreeHouse\IoBundle\Bridge\WorkerBundle\Executor\SourceProcessExecutor;
use TreeHouse\IoBundle\Event\SourceEvent;
use TreeHouse\WorkerBundle\QueueManager;

class SourceModificationListener
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
    public function onSourceProcess(SourceEvent $event)
    {
        $this->queueManager->addForObject(SourceProcessExecutor::NAME, $event->getSource());
    }
}
