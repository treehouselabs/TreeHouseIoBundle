<?php

namespace TreeHouse\IoBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class CleanupEvent extends Event
{
    /**
     * @var integer
     */
    protected $numCleaned;

    /**
     * @param integer $numCleaned
     */
    public function __construct($numCleaned)
    {
        $this->numCleaned = $numCleaned;
    }

    /**
     * @return integer
     */
    public function getNumCleaned()
    {
        return $this->numCleaned;
    }
}
