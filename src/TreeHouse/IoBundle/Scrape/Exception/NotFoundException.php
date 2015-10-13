<?php

namespace TreeHouse\IoBundle\Scrape\Exception;

use Psr\Http\Message\ResponseInterface;

class NotFoundException extends UnexpectedResponseException
{
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
            $message = 'Got a "not found" response for url';
        }

        parent::__construct($url, $response, $message, $code, $previous);
    }
}
