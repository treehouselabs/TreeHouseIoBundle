<?php

namespace TreeHouse\IoBundle\Scrape\Event;

use Symfony\Component\HttpFoundation\Response;
use TreeHouse\IoBundle\Entity\Scraper;

class ScrapeResponseEvent extends ScrapeUrlEvent
{
    /**
     * @var Response
     */
    protected $response;

    /**
     * @param Scraper  $scraper
     * @param string   $url
     * @param Response $response
     */
    public function __construct(Scraper $scraper, $url, Response $response)
    {
        parent::__construct($scraper, $url);

        $this->response = $response;
    }

    /**
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }
}
