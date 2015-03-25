<?php

namespace TreeHouse\IoBundle\Scrape\Crawler\RateLimit;

interface EnablingRateLimitInterface
{
    /**
     * Disables the rate limit
     *
     * @return void
     */
    public function disable();

    /**
     * Enables the rate limit
     *
     * @return void
     */
    public function enable();
}
