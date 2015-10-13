<?php

namespace TreeHouse\IoBundle\Scrape\Crawler;

use Psr\Http\Message\ResponseInterface;
use TreeHouse\IoBundle\Scrape\Crawler\Client\ClientInterface;
use TreeHouse\IoBundle\Scrape\Crawler\Log\RequestLoggerInterface;
use TreeHouse\IoBundle\Scrape\Crawler\RateLimit\RateLimitInterface;
use TreeHouse\IoBundle\Scrape\Exception\CrawlException;
use TreeHouse\IoBundle\Scrape\Exception\NotFoundException;
use TreeHouse\IoBundle\Scrape\Exception\RateLimitException;
use TreeHouse\IoBundle\Scrape\Exception\UnexpectedResponseException;

interface CrawlerInterface
{
    /**
     * @return ClientInterface
     */
    public function getClient();

    /**
     * @return RequestLoggerInterface
     */
    public function getLogger();

    /**
     * @return RateLimitInterface
     */
    public function getRateLimit();

    /**
     * Returns the response of the last crawled page
     *
     * @throws \RuntimeException When no page has been crawled yet.
     *
     * @return ResponseInterface
     */
    public function getLastResponse();

    /**
     * Returns the last crawled url.
     * When following redirects, the url is updated with the effective url.
     *
     * @return string
     */
    public function getLastUrl();

    /**
     * Requests an url and returns the resulting contents.
     * After crawling you can call {@link getLastResponse()} to get the entire
     * response, and {@link getLastUrl()} to get the effectively crawled url,
     * meaning after normalizing and following redirects.
     *
     * @param string $url
     *
     * @throws RateLimitException          When the rate limit has been reached
     * @throws NotFoundException           When the requested page was not found
     * @throws UnexpectedResponseException When the result was not the page we expected
     * @throws CrawlException              When anything else goes wrong while crawling
     *
     * @return string
     */
    public function crawl($url);

    /**
     * Returns the urls of the pages to crawl next.
     * Only works when a page has already been crawled, obviously.
     *
     * @throws \RuntimeException When no page has been crawled yet
     *
     * @return string[]
     */
    public function getNextUrls();
}
