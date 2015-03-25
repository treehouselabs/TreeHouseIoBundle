<?php

namespace TreeHouse\IoBundle\Scrape\Crawler;

use Symfony\Component\HttpFoundation\Response;
use TreeHouse\IoBundle\Scrape\Crawler\Client\ClientInterface;
use TreeHouse\IoBundle\Scrape\Crawler\Log\RequestLoggerInterface;
use TreeHouse\IoBundle\Scrape\Crawler\RateLimit\RateLimitInterface;

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
     * @return Response
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
     * @param string $url
     *
     * @return string
     */
    public function crawl($url);

    /**
     * @return string[]
     */
    public function getNextUrls();
}
