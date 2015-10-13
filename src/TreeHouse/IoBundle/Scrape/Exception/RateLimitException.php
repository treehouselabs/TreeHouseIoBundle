<?php

namespace TreeHouse\IoBundle\Scrape\Exception;

class RateLimitException extends CrawlException
{
    /**
     * @var \DateTime
     */
    protected $retryDate;

    /**
     * @param string     $url
     * @param string     $message
     * @param \DateTime  $retryDate
     * @param int        $code
     * @param \Exception $previous
     */
    public function __construct($url, $message = '', \DateTime $retryDate = null, $code = 0, \Exception $previous = null)
    {
        parent::__construct($url, $message, $code, $previous);

        if ($retryDate instanceof \DateTime && $retryDate < new \DateTime()) {
            throw new \InvalidArgumentException('$retryDate cannot be in the past');
        }

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
