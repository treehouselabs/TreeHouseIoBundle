<?php

namespace TreeHouse\IoBundle\Scrape\Crawler\RateLimit;

interface EnablingRateLimitInterface
{
    /**
     * Disables the rate limit.
     */
    public function disable();

    /**
     * Enables the rate limit.
     */
    public function enable();
}
