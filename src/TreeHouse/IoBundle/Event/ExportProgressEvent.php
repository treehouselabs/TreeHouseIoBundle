<?php

namespace TreeHouse\IoBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class ExportProgressEvent extends Event
{
    /**
     * @var integer
     */
    protected $current;

    /**
     * @var integer
     */
    protected $total;

    /**
     * @param integer $current
     * @param integer $total
     */
    public function __construct($current, $total)
    {
        $this->current = $current;
        $this->total = $total;
    }

    /**
     * @return integer
     */
    public function getCurrent()
    {
        return $this->current;
    }

    /**
     * @return integer
     */
    public function getTotal()
    {
        return $this->total;
    }
}
