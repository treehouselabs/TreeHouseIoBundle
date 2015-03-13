<?php

namespace TreeHouse\IoBundle\Bridge\WorkerBundle\EventListener;

use FM\WorkerBundle\QueueManager;
use TreeHouse\IoBundle\Event\SourceEvent;
use TreeHouse\IoBundle\Bridge\WorkerBundle\Executor\SourceProcessExecutor;

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
