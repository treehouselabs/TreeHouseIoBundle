<?php

namespace TreeHouse\IoBundle\Scrape\Event;

use Symfony\Component\EventDispatcher\Event;
use TreeHouse\IoBundle\Entity\Scraper;

class ScrapeUrlEvent extends Event
{
    /**
     * @var Scraper
     */
    protected $scraper;

    /**
     * @var string
     */
    protected $url;

    /**
     * @param Scraper $scraper
     * @param string  $url
     */
    public function __construct(Scraper $scraper, $url)
    {
        $this->scraper = $scraper;
        $this->url     = $url;
    }

    /**
     * @return Scraper
     */
    public function getScraper()
    {
        return $this->scraper;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }
}
