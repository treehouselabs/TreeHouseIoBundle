<?php

namespace TreeHouse\IoBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use TreeHouse\IoBundle\Entity\Import;
use TreeHouse\IoBundle\Import\ImportStorage;
use TreeHouse\IoBundle\Import\Log\ItemLoggerInterface;

/**
 * Removes associated stuff when an import is removed.
 */
class ImportRemovalListener
{
    /**
     * @var ImportStorage
     */
    protected $importStorage;

    /**
     * @var ItemLoggerInterface
     */
    protected $itemLogger;

    /**
     * @param ImportStorage       $importStorage
     * @param ItemLoggerInterface $itemLogger
     */
    public function __construct(ImportStorage $importStorage, ItemLoggerInterface $itemLogger = null)
    {
        $this->importStorage = $importStorage;
        $this->itemLogger = $itemLogger;
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function preRemove(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        if ($entity instanceof Import) {
            $this->removeItemLog($entity);
            $this->removeFeed($entity);
        }
    }

    /**
     * @param Import $import
     */
    protected function removeItemLog(Import $import)
    {
        if (!$this->itemLogger) {
            return;
        }

        $this->itemLogger->removeLog($import);
    }

    /**
     * @param Import $import
     */
    protected function removeFeed(Import $import)
    {
        $this->importStorage->removeImport($import);
    }
}
