<?php

namespace TreeHouse\IoBundle\Bridge\WorkerBundle\EventListener;

use FM\WorkerBundle\QueueManager;
use TreeHouse\IoBundle\Bridge\WorkerBundle\Executor\ImportPartExecutor;
use TreeHouse\IoBundle\Import\Event\PartEvent;

class ImportScheduleListener
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
     * @param PartEvent $event
     */
    public function onPartScheduled(PartEvent $event)
    {
        $this->queueManager->addForObject(ImportPartExecutor::NAME, $event->getPart());
    }
}
