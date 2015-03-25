<?php

namespace TreeHouse\IoBundle\Scrape\Crawler\RateLimit;

trait EnablingTrait
{
    /**
     * @var boolean
     */
    protected $enabled = true;

    /**
     * Disables
     */
    public function disable()
    {
        $this->enabled = false;
    }

    /**
     * Enables
     */
    public function enable()
    {
        $this->enabled = true;
    }

    /**
     * @return boolean
     */
    public function isEnabled()
    {
        return $this->enabled;
    }
}
