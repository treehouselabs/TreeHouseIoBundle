<?php

namespace TreeHouse\IoBundle\Scrape;

final class ScraperEvents
{
    // various item import outcomes
    const ITEM_SUCCESS          = 'io.scrape.item.success';
    const ITEM_FAILED           = 'io.scrape.item.failed';
    const ITEM_SKIPPED          = 'io.scrape.item.skipped';

    const SCRAPE_NEXT_URL       = 'io.scrape.url.next';
    const SCRAPE_URL_NOT_OK     = 'io.scrape.url.not_ok';
    const RATE_LIMIT_REACHED    = 'io.scrape.rate_limit.reached';
}
