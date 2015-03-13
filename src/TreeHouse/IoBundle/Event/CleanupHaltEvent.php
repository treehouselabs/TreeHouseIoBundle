<?php

namespace TreeHouse\IoBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class CleanupHaltEvent extends Event
{
    /**
     * @var integer
     */
    protected $count;

    /**
     * @var integer
     */
    protected $total;

    /**
     * @var integer
     */
    protected $max;

    /**
     * @param integer $count
     * @param integer $total
     * @param integer $max
     */
    public function __construct($count, $total, $max)
    {
        $this->count  = $count;
        $this->total  = $total;
        $this->max    = $max;
    }

    /**
     * @param integer $count
     */
    public function setCount($count)
    {
        $this->count = $count;
    }

    /**
     * @return integer
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * @param integer $total
     */
    public function setTotal($total)
    {
        $this->total = $total;
    }

    /**
     * @return integer
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * @param integer $max
     */
    public function setMax($max)
    {
        $this->max = $max;
    }

    /**
     * @return integer
     */
    public function getMax()
    {
        return $this->max;
    }
}
