<?php

namespace TreeHouse\IoBundle\Scrape\Event;

use TreeHouse\IoBundle\Entity\Scraper;

class RateLimitEvent extends ScrapeUrlEvent
{
    /**
     * @var \DateTime
     */
    protected $retryDate;

    /**
     * @param Scraper   $scraper
     * @param string    $url
     * @param \DateTime $retryDate
     */
    public function __construct(Scraper $scraper, $url, \DateTime $retryDate = null)
    {
        parent::__construct($scraper, $url);

        $this->retryDate = $retryDate;
    }

    /**
     * @return \DateTime
     */
    public function getRetryDate()
    {
        return $this->retryDate;
    }
}
