<?php

namespace TreeHouse\IoBundle\Scrape\Crawler\RateLimit;

class NoRateLimit implements RateLimitInterface
{
    /**
     * @inheritdoc
     */
    public function limitReached()
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function getLimit()
    {
        return 'none';
    }

    /**
     * @inheritdoc
     */
    public function getRetryDate()
    {
        // noop
    }
}
