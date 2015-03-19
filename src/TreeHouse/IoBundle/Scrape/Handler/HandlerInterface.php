<?php

namespace TreeHouse\IoBundle\Scrape\Handler;

use TreeHouse\IoBundle\Model\SourceInterface;
use TreeHouse\IoBundle\Scrape\ScrapedItemBag;

interface HandlerInterface
{
    /**
     * @param ScrapedItemBag $item
     *
     * @return SourceInterface
     */
    public function handle(ScrapedItemBag $item);
}
