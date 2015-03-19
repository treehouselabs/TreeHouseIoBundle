<?php

namespace TreeHouse\IoBundle\Scrape\Event;

use TreeHouse\IoBundle\Model\SourceInterface;
use TreeHouse\IoBundle\Scrape\ScrapedItemBag;
use TreeHouse\IoBundle\Scrape\Scraper;

class SuccessItemEvent extends ItemEvent
{
    /**
     * @var SourceInterface
     */
    protected $result;

    /**
     * @param Scraper         $scraper
     * @param ScrapedItemBag  $item
     * @param SourceInterface $result
     */
    public function __construct(Scraper $scraper, ScrapedItemBag $item, SourceInterface $result)
    {
        parent::__construct($scraper, $item);

        $this->result = $result;
    }

    /**
     * @param SourceInterface $result The result
     */
    public function setResult($result)
    {
        $this->result = $result;
    }

    /**
     * @return SourceInterface
     */
    public function getResult()
    {
        return $this->result;
    }
}
