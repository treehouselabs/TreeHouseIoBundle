<?php

namespace TreeHouse\IoBundle\Scrape\Crawler\Log;

/**
 * In-memory request logger. Use for very basic or testing purposes.
 *
 * CAUTION: it's not a good idea to use this when crawling large amounts of pages,
 * since memory usage keeps increasing, as well as the time consumed in this class.
 * Also this class does not work when working with multiple processes.
 *
 * You should configure one of the other loggers.
 */
class ArrayRequestLogger implements RequestLoggerInterface
{
    /**
     * @var array<integer, string[]>
     */
    protected $requests = [];

    /**
     * @inheritdoc
     */
    public function logRequest($url, \DateTime $date = null)
    {
        if (null === $date) {
            $date = new \DateTime();
        }

        $hashKey = $date->getTimestamp();
        $this->requests[$hashKey][] = $url;
    }

    /**
     * @inheritdoc
     */
    public function getRequestsSince(\DateTime $date = null)
    {
        $start = $date ? $date->getTimestamp() : 0;

        $requests = [];
        foreach ($this->requests as $time => $reqs) {
            if ($time >= $start) {
                foreach ($reqs as $req) {
                    array_unshift($requests, [$time, $req]);
                }
            }
        }

        return $requests;
    }
}
