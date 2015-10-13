<?php

namespace TreeHouse\IoBundle\Scrape\Event;

use Psr\Http\Message\ResponseInterface;
use TreeHouse\IoBundle\Entity\Scraper;

class ScrapeResponseEvent extends ScrapeUrlEvent
{
    /**
     * @var ResponseInterface
     */
    protected $response;

    /**
     * @param Scraper           $scraper
     * @param string            $url
     * @param ResponseInterface $response
     */
    public function __construct(Scraper $scraper, $url, ResponseInterface $response)
    {
        parent::__construct($scraper, $url);

        $this->response = $response;
    }

    /**
     * @return ResponseInterface
     */
    public function getResponse()
    {
        return $this->response;
    }
}
