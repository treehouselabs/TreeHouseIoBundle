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
     * @param \DateTime $date
     *
     * @return \string[]
     */
    public function getRequestsSince(\DateTime $date);
}
