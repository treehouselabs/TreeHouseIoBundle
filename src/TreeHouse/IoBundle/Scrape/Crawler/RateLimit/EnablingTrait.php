<?php

namespace TreeHouse\IoBundle\Scrape\Crawler\RateLimit;

trait EnablingTrait
{
    /**
     * @var bool
     */
    protected $enabled = true;

    /**
     * Disables.
     */
    public function disable()
    {
        $this->enabled = false;
    }

    /**
     * Enables.
     */
    public function enable()
    {
        $this->enabled = true;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }
}
