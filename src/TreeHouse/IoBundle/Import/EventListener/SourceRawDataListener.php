<?php

namespace TreeHouse\IoBundle\Import\EventListener;

use TreeHouse\Feeder\Event\ResourceSerializeEvent;
use TreeHouse\IoBundle\Import\Event\ItemEvent;
use TreeHouse\IoBundle\Import\Event\SuccessItemEvent;

/**
 * Listener that sets the raw (unprocessed) data on a source. Useful for debugging purposes.
 */
class SourceRawDataListener
{
    /**
     * Raw source string
     *
     * @var string
     */
    protected $rawData;

    /**
     * Catches the raw xml for an item
     *
     * @param ResourceSerializeEvent $e
     */
    public function onResourcePreSerialize(ResourceSerializeEvent $e)
    {
        $xml = $e->getItem();

        $doc = new \DOMDocument();
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = true;
        $doc->loadXML($xml);

        $this->rawData = $doc->saveXML();
    }

    /**
     * Sets the raw data on the resulting Source
     *
     * @param SuccessItemEvent $e
     */
    public function onItemSuccess(SuccessItemEvent $e)
    {
        $source = $e->getResult();

        $source->setRawData($this->rawData);

        $this->rawData = null;
    }

    /**
     * Cleanup previous raw data
     *
     * @param ItemEvent $e
     */
    public function onItemFinish(ItemEvent $e)
    {
        $this->rawData = null;
    }
}
