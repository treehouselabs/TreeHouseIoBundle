<?php

namespace TreeHouse\IoBundle\Scrape\Crawler\RateLimit;

use TreeHouse\IoBundle\Scrape\Crawler\Log\RequestLoggerInterface;

/**
 * Rate limit for a maximum amount of requests per minute.
 *
 * The implementation actually works with seconds, to ensure the spreading of requests,
 * rather than doing x simultaneous requests at the start of each minute.
 */
class RpmRateLimit implements RateLimitInterface, EnablingRateLimitInterface
{
    use EnablingTrait;

    /**
     * @var RequestLoggerInterface
     */
    protected $logger;

    /**
     * @var integer
     */
    protected $rpm;

    /**
     * The number of requests that may occur in a single time unit
     *
     * @var float
     */
    protected $maxAmount;

    /**
     * The number of seconds that constitutes a minimum time unit to work with
     *
     * @var integer
     */
    protected $timeUnit = 1;

    /**
     * @param RequestLoggerInterface $logger
     * @param integer                $rpm
     */
    public function __construct(RequestLoggerInterface $logger, $rpm)
    {
        $this->logger = $logger;
        $this->setRpm($rpm);
    }

    /**
     * @return integer
     */
    public function getRpm()
    {
        return $this->rpm;
    }

    /**
     * @param integer $rpm
     */
    public function setRpm($rpm)
    {
        $this->rpm = (integer) $rpm;

        $this->maxAmount = $this->rpm / 60;
        if ($this->maxAmount < 1) {
            $this->timeUnit = 1 / ($this->rpm / 60);
            $this->maxAmount *= $this->timeUnit;
        } else {
            $this->maxAmount = ceil($this->maxAmount);
        }
    }

    /**
     * @inheritdoc
     */
    public function limitReached()
    {
        if (!$this->enabled) {
            return false;
        }

        $date     = new \DateTime(sprintf('-%d seconds', $this->timeUnit));
        $requests = $this->logger->getRequestsSince($date);

        return sizeof($requests) >= $this->maxAmount;
    }

    /**
     * @inheritdoc
     */
    public function getLimit()
    {
        return sprintf('%d requests/minute', $this->rpm);
    }

    /**
     * @inheritdoc
     */
    public function getRetryDate()
    {
        return new \DateTime(sprintf('+%d seconds', $this->timeUnit));
    }
}
