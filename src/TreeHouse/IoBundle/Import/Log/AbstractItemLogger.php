<?php

namespace TreeHouse\IoBundle\Import\Log;

use TreeHouse\IoBundle\Entity\Import;
use TreeHouse\IoBundle\Import\Event\FailedItemEvent;
use TreeHouse\IoBundle\Import\Event\SkippedItemEvent;
use TreeHouse\IoBundle\Import\Event\SuccessItemEvent;
use TreeHouse\IoBundle\Import\ImportEvents;

abstract class AbstractItemLogger implements ItemLoggerInterface
{
    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents()
    {
        return [
            ImportEvents::ITEM_SUCCESS => 'logSuccessItem',
            ImportEvents::ITEM_FAILED => 'logFailedItem',
            ImportEvents::ITEM_SKIPPED => 'logSkippedItem',
        ];
    }

    /**
     * @inheritdoc
     */
    public function logSuccessItem(SuccessItemEvent $event)
    {
        $ident = $this->getLogIdent($event->getImporter()->getImport());
        $source = $event->getResult();
        $originalId = $event->getItem()->getOriginalId();
        $context = [
            'result' => 'success',
            'item' => (string) $event->getItem(),
            'source' => $source->getId(),
        ];

        $this->doLog($ident, $originalId, $context);
    }

    /**
     * @inheritdoc
     */
    public function logFailedItem(FailedItemEvent $event)
    {
        $ident = $this->getLogIdent($event->getImporter()->getImport());
        $originalId = $event->getItem()->getOriginalId();
        $context = [
            'result' => 'failed',
            'item' => (string) $event->getItem(),
            'reason' => $event->getReason(),
        ];

        $this->doLog($ident, $originalId, $context);
    }

    /**
     * @inheritdoc
     */
    public function logSkippedItem(SkippedItemEvent $event)
    {
        $ident = $this->getLogIdent($event->getImporter()->getImport());
        $originalId = $event->getItem()->getOriginalId();
        $context = [
            'result' => 'skipped',
            'item' => (string) $event->getItem(),
            'reason' => $event->getReason(),
        ];

        $this->doLog($ident, $originalId, $context);
    }

    /**
     * @inheritdoc
     */
    public function removeLog(Import $import)
    {
        $this->doRemoveLog($this->getLogIdent($import));
    }

    /**
     * @param Import $import
     *
     * @return string
     */
    protected function getLogIdent(Import $import)
    {
        return sprintf('import-%d', $import->getId());
    }

    /**
     * @param string $ident
     * @param string $originalId
     * @param array  $context
     */
    abstract protected function doLog($ident, $originalId, array $context);

    /**
     * @param string $ident
     */
    abstract protected function doRemoveLog($ident);
}
