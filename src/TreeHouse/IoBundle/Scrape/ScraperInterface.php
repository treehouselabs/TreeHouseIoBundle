<?php

namespace TreeHouse\IoBundle\Scrape;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use TreeHouse\IoBundle\Entity\Scraper as ScraperEntity;
use TreeHouse\IoBundle\Scrape\Crawler\CrawlerInterface;
use TreeHouse\IoBundle\Scrape\Exception\CrawlException;
use TreeHouse\IoBundle\Scrape\Exception\RateLimitException;
use TreeHouse\IoBundle\Scrape\Exception\UnexpectedResponseException;

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
     * Makes the scraper asynchronous. Where available, an async scraper is
     * non-blocking and tries to delegate as much work using events (see ScraperEvents).
     *
     * @param bool $async
     */
    public function setAsync($async);

    /**
     * @return bool
     */
    public function isAsync();

    /**
     * Scrapes a url and the next urls found on the page.
     *
     * @param ScraperEntity $scraper  A scraper entity with a defined crawler, parser, and handler
     * @param string        $url      The url to scrape
     * @param bool          $continue Whether to continue scraping the links found on the current page
     *
     * @throws RateLimitException          When the rate limit has been reached
     * @throws UnexpectedResponseException When the result was not the page we expected
     * @throws CrawlException              When anything else goes wrong while crawling
     */
    public function scrape(ScraperEntity $scraper, $url, $continue = true);

    /**
     * Continues scraping using the urls found on the current page.
     * This only works when a previous page has been scraped.
     *
     * @param ScraperEntity $scraper
     *
     * @throws \RuntimeException When no page has been scraped yet
     */
    public function scrapeNext(ScraperEntity $scraper);

    /**
     * Does a non-blocking scrape operation. Depending on the implementation,
     * this will mean adding the url to some sort of queueing system.
     *
     * @param ScraperEntity $scraper
     * @param string        $url
     * @param \DateTime     $date
     */
    public function scrapeAfter(ScraperEntity $scraper, $url, \DateTime $date);
}
