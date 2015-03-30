<?php

namespace TreeHouse\IoBundle\Scrape\Exception;

use Symfony\Component\HttpFoundation\Response;

class NotFoundException extends UnexpectedResponseException
{
    /**
     * @param string     $url
     * @param Response   $response
     * @param string     $message
     * @param integer    $code
     * @param \Exception $previous
     */
    public function __construct($url, Response $response, $message = '', $code = 0, \Exception $previous = null)
    {
        if (empty($message)) {
            $message = 'Got a "not found" response for url';
        }

        parent::__construct($url, $response, $message, $code, $previous);
    }
}
