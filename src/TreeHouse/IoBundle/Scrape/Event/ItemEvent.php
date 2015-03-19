<?php

namespace TreeHouse\IoBundle\Scrape\Event;

use Symfony\Component\EventDispatcher\Event;
use TreeHouse\IoBundle\Item\ItemBag;
use TreeHouse\IoBundle\Scrape\Scraper;

class ItemEvent extends Event
{
    /**
     * @var Scraper
     */
    protected $scraper;

    /**
     * @var ItemBag
     */
    protected $item;

    /**
     * @param Scraper $scraper The scraper where the event originated
     * @param ItemBag $item    The item
     */
    public function __construct(Scraper $scraper, ItemBag $item)
    {
        $this->scraper = $scraper;
        $this->item    = $item;
    }

    /**
     * @return Scraper
     */
    public function getScraper()
    {
        return $this->scraper;
    }

    /**
     * @return ItemBag
     */
    public function getItem()
    {
        return $this->item;
    }
}
