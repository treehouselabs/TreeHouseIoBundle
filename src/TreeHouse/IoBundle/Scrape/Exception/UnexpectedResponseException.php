<?php

namespace TreeHouse\IoBundle\Scrape\Exception;

use Symfony\Component\HttpFoundation\Response;

class UnexpectedResponseException extends CrawlException
{
    /**
     * @var Response
     */
    protected $response;

    /**
     * @param string     $url
     * @param Response   $response
     * @param integer    $code
     * @param \Exception $previous
     */
    public function __construct($url, Response $response, $code = 0, \Exception $previous = null)
    {
        if (empty($message)) {
            $message = sprintf('Got a %d response instead of a 200 OK response', $response->getStatusCode());
        }

        parent::__construct($url, $message, $code, $previous);

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
