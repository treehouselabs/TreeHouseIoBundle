<?php

namespace TreeHouse\IoBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class CleanupHaltEvent extends Event
{
    /**
     * @var int
     */
    protected $count;

    /**
     * @var int
     */
    protected $total;

    /**
     * @var int
     */
    protected $max;

    /**
     * @param int $count
     * @param int $total
     * @param int $max
     */
    public function __construct($count, $total, $max)
    {
        $this->count = $count;
        $this->total = $total;
        $this->max = $max;
    }

    /**
     * @param int $count
     */
    public function setCount($count)
    {
        $this->count = $count;
    }

    /**
     * @return int
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * @param int $total
     */
    public function setTotal($total)
    {
        $this->total = $total;
    }

    /**
     * @return int
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * @param int $max
     */
    public function setMax($max)
    {
        $this->max = $max;
    }

    /**
     * @return int
     */
    public function getMax()
    {
        return $this->max;
    }
}
