<?php

namespace TreeHouse\IoBundle\Bridge\WorkerBundle\EventListener;

use TreeHouse\WorkerBundle\QueueManager;
use TreeHouse\IoBundle\Bridge\WorkerBundle\Executor\ImportPartExecutor;
use TreeHouse\IoBundle\Import\Event\PartEvent;

class ImportScheduleListener
{
    /**
     * @var QueueManager
     */
    protected $queueManager;

    /**
     * @var integer
     */
    protected $timeToRun;

    /**
     * @param QueueManager $queueManager
     * @param integer      $ttr
     */
    public function __construct(QueueManager $queueManager, $ttr = 300)
    {
        $this->queueManager = $queueManager;
        $this->timeToRun    = $ttr;
    }

    /**
     * @param PartEvent $event
     */
    public function onPartScheduled(PartEvent $event)
    {
        $this->queueManager->addForObject(ImportPartExecutor::NAME, $event->getPart(), null, null, $this->timeToRun);
    }
}
