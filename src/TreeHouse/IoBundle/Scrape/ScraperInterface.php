<?php

namespace TreeHouse\IoBundle\Scrape;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use TreeHouse\IoBundle\Entity\Scraper as ScraperEntity;
use TreeHouse\IoBundle\Scrape\Crawler\CrawlerInterface;

interface ScraperInterface
{
    /**
     * @return CrawlerInterface
     */
    public function getCrawler();

    /**
     * @return EventDispatcherInterface
     */
    public function getEventDispatcher();

    /**
     * @param boolean $async
     */
    public function setAsync($async);

    /**
     * @return boolean
     */
    public function isAsync();

    /**
     * @param ScraperEntity $scraper
     * @param string        $url
     *
     * @return boolean True when the scrape succeeded, regardless of the outcome, false otherwise.
     */
    public function scrape(ScraperEntity $scraper, $url);
}
