<?php

namespace TreeHouse\IoBundle\Import;

class ImportResult
{
    /**
     * @var integer
     */
    protected $startTime;

    /**
     * @var integer
     */
    protected $endTime;

    /**
     * @var integer
     */
    protected $success = 0;

    /**
     * @var integer
     */
    protected $failed = 0;

    /**
     * @var integer
     */
    protected $skipped = 0;

    /**
     * @param integer $startTime
     */
    public function __construct($startTime = null)
    {
        $this->startTime = $startTime ?: microtime(true);
    }

    /**
     * @return int
     */
    public function getStartTime()
    {
        return $this->startTime;
    }

    /**
     * @param int $startTime
     */
    public function setStartTime($startTime)
    {
        $this->startTime = $startTime;
    }

    /**
     * @return int
     */
    public function getEndTime()
    {
        return $this->endTime;
    }

    /**
     * @param int $endTime
     */
    public function setEndTime($endTime)
    {
        $this->endTime = $endTime;
    }

    /**
     * Adds 1 to success
     */
    public function incrementSuccess()
    {
        $this->success++;
    }

    /**
     * @param int $success
     */
    public function setSuccess($success)
    {
        $this->success = $success;
    }

    /**
     * @return int
     */
    public function getSuccess()
    {
        return $this->success;
    }

    /**
     * Adds 1 to failed
     */
    public function incrementFailed()
    {
        $this->failed++;
    }

    /**
     * @param int $failed
     */
    public function setFailed($failed)
    {
        $this->failed = $failed;
    }

    /**
     * @return int
     */
    public function getFailed()
    {
        return $this->failed;
    }

    /**
     * @return int
     */
    public function getSkipped()
    {
        return $this->skipped;
    }

    /**
     * @param int $skipped
     */
    public function setSkipped($skipped)
    {
        $this->skipped = $skipped;
    }

    /**
     * Adds 1 to skipped
     */
    public function incrementSkipped()
    {
        $this->skipped++;
    }

    /**
     * Returns the total number of items that were in the feed, whether they were processed or skipped.
     *
     * @return integer
     */
    public function getTotal()
    {
        return $this->getSkipped() + $this->getProcessed();
    }

    /**
     * Returns the number of items that were actually handled by the import, as opposed to skipped items.
     *
     * @return integer
     */
    public function getProcessed()
    {
        return $this->getSuccess() + $this->getFailed();
    }

    /**
     * @return float
     */
    public function getElapsedTime()
    {
        return microtime(true) - $this->startTime;
    }
}
