<?php

namespace TreeHouse\IoBundle\EventListener;

use TreeHouse\Feeder\Event\ResourceSerializeEvent;
use TreeHouse\IoBundle\Import\Event\SuccessItemEvent as SuccessImportItemEvent;
use TreeHouse\IoBundle\Model\SourceInterface;
use TreeHouse\IoBundle\Scrape\Event\SuccessItemEvent as SuccessScrapeItemEvent;
use TreeHouse\IoBundle\Scrape\ScrapedItemBag;

/**
 * Listener that sets the raw (unprocessed) data on a source. Useful for debugging purposes.
 */
class SourceRawDataListener
{
    /**
     * Raw source string.
     *
     * @var string
     */
    protected $rawData;

    /**
     * Catches the raw xml for an item.
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
     * @param SuccessImportItemEvent $e
     */
    public function onImportItemSuccess(SuccessImportItemEvent $e)
    {
        $this->setRawData($e->getResult());
    }

    /**
     * @param SuccessScrapeItemEvent $e
     */
    public function onScrapeItemSuccess(SuccessScrapeItemEvent $e)
    {
        /** @var ScrapedItemBag $item */
        $item = $e->getItem();
        $this->rawData = $item->getOriginalData();

        $this->setRawData($e->getResult());
    }

    /**
     * Sets the raw data on the resulting Source.
     *
     * @param SourceInterface $source
     */
    protected function setRawData(SourceInterface $source)
    {
        $source->setRawData(trim($this->rawData));
        $this->rawData = null;
    }
}
