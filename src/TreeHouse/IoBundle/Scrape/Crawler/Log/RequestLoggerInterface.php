<?php

namespace TreeHouse\IoBundle\Scrape\Crawler\Log;

interface RequestLoggerInterface
{
    /**
     * @param string    $url
     * @param \DateTime $date
     */
    public function logRequest($url, \DateTime $date = null);

    /**
     * Returns an array of requests since a specific date.
     *
     * @param \DateTime $date The interval to get the logged requests for.
     *                        When `null` is given, all logged requests are returned.
     *
     * @return array<integer, string> The requests as an array of [timestamp, url] tuples
     */
    public function getRequestsSince(\DateTime $date = null);
}
