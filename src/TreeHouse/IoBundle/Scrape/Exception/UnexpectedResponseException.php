<?php

namespace TreeHouse\IoBundle\Scrape\Exception;

use Psr\Http\Message\ResponseInterface;

class UnexpectedResponseException extends CrawlException
{
    /**
     * @var ResponseInterface
     */
    protected $response;

    /**
     * @param string     $url
     * @param ResponseInterface   $response
     * @param string     $message
     * @param integer    $code
     * @param \Exception $previous
     */
    public function __construct($url, ResponseInterface $response, $message = '', $code = 0, \Exception $previous = null)
    {
        if (empty($message)) {
            $message = sprintf('Got a %d response instead of a 200 OK response', $response->getStatusCode());
        }

        parent::__construct($url, $message, $code, $previous);

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
