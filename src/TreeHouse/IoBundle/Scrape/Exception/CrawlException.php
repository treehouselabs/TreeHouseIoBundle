<?php

namespace TreeHouse\IoBundle\Scrape\Exception;

/**
 * Base class for all crawling related exceptions
 */
class CrawlException extends \RuntimeException
{
    /**
     * @var string
     */
    protected $url;

    /**
     * @param string     $url
     * @param string     $message
     * @param integer    $code
     * @param \Exception $previous
     */
    public function __construct($url, $message = '', $code = 0, \Exception $previous = null)
    {
        $this->url = $url;

        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }
}
