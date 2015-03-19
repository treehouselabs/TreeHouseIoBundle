<?php

namespace TreeHouse\IoBundle\Scrape\Crawler\Client;

use Symfony\Component\HttpFoundation\Response;
use TreeHouse\IoBundle\Scrape\Exception\CrawlException;

interface ClientInterface
{
    /**
     * @param string $url
     * @param string $userAgent
     *
     * @throws CrawlException
     *
     * @return array<string, Response> A tuple consisting of the effective url and the response
     */
    public function fetch($url, $userAgent = null);
}
