<?php

namespace TreeHouse\IoBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class ExportProgressEvent extends Event
{
    /**
     * @var int
     */
    protected $current;

    /**
     * @var int
     */
    protected $total;

    /**
     * @param int $current
     * @param int $total
     */
    public function __construct($current, $total)
    {
        $this->current = $current;
        $this->total = $total;
    }

    /**
     * @return int
     */
    public function getCurrent()
    {
        return $this->current;
    }

    /**
     * @return int
     */
    public function getTotal()
    {
        return $this->total;
    }
}
