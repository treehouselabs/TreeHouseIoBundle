<?php

namespace TreeHouse\IoBundle\Scrape\Crawler\Client;

use Psr\Http\Message\ResponseInterface;
use TreeHouse\IoBundle\Scrape\Exception\CrawlException;

interface ClientInterface
{
    /**
     * @param string $url
     * @param string $userAgent
     *
     * @throws CrawlException
     *
     * @return array<string, ResponseInterface> A tuple consisting of the effective url and the response
     */
    public function fetch($url, $userAgent = null);
}
