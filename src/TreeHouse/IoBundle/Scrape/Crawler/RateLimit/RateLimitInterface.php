<?php

namespace TreeHouse\IoBundle\Scrape\Crawler\RateLimit;

interface RateLimitInterface
{
    /**
     * @return bool
     */
    public function limitReached();

    /**
     * Returns a string representation of the rate limit.
     *
     * @return string
     */
    public function getLimit();

    /**
     * @return \DateTime|null
     */
    public function getRetryDate();
}
