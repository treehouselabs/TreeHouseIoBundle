<?php

namespace TreeHouse\IoBundle\Bridge\WorkerBundle\EventListener;

use TreeHouse\IoBundle\Bridge\WorkerBundle\Executor\ImportPartExecutor;
use TreeHouse\IoBundle\Import\Event\PartEvent;
use TreeHouse\WorkerBundle\QueueManager;

class ImportScheduleListener
{
    /**
     * @var QueueManager
     */
    protected $queueManager;

    /**
     * @var int
     */
    protected $timeToRun;

    /**
     * @param QueueManager $queueManager
     * @param int          $ttr
     */
    public function __construct(QueueManager $queueManager, $ttr = 300)
    {
        $this->queueManager = $queueManager;
        $this->timeToRun = $ttr;
    }

    /**
     * @param PartEvent $event
     */
    public function onPartScheduled(PartEvent $event)
    {
        $this->queueManager->addForObject(ImportPartExecutor::NAME, $event->getPart(), null, null, $this->timeToRun);
    }
}
