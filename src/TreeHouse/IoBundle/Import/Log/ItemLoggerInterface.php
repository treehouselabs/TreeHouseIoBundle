<?php

namespace TreeHouse\IoBundle\Import\Log;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use TreeHouse\IoBundle\Entity\Import;
use TreeHouse\IoBundle\Import\Event\FailedItemEvent;
use TreeHouse\IoBundle\Import\Event\SkippedItemEvent;
use TreeHouse\IoBundle\Import\Event\SuccessItemEvent;

interface ItemLoggerInterface extends EventSubscriberInterface
{
    /**
     * @param SuccessItemEvent $event
     */
    public function logSuccessItem(SuccessItemEvent $event);

    /**
     * @param FailedItemEvent $event
     */
    public function logFailedItem(FailedItemEvent $event);

    /**
     * @param SkippedItemEvent $event
     */
    public function logSkippedItem(SkippedItemEvent $event);

    /**
     * @param Import $import
     */
    public function removeLog(Import $import);

    /**
     * @param Import $import
     *
     * @return \Generator
     */
    public function getImportedItems(Import $import);
}
