<?php

namespace TreeHouse\IoBundle\Scrape\Event;

use TreeHouse\IoBundle\Scrape\ScrapedItemBag;
use TreeHouse\IoBundle\Scrape\Scraper;

class SkippedItemEvent extends ItemEvent
{
    /**
     * @var string
     */
    protected $reason;

    /**
     * @param Scraper        $scraper
     * @param ScrapedItemBag $item
     * @param string         $reason
     */
    public function __construct(Scraper $scraper, ScrapedItemBag $item, $reason)
    {
        parent::__construct($scraper, $item);

        $this->reason = $reason;
    }

    /**
     * @param string $reason
     */
    public function setReason($reason)
    {
        $this->reason = $reason;
    }

    /**
     * @return string
     */
    public function getReason()
    {
        return $this->reason;
    }
}
