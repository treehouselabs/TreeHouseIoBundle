<?php

namespace TreeHouse\IoBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class CleanupEvent extends Event
{
    /**
     * @var int
     */
    protected $numCleaned;

    /**
     * @param int $numCleaned
     */
    public function __construct($numCleaned)
    {
        $this->numCleaned = $numCleaned;
    }

    /**
     * @return int
     */
    public function getNumCleaned()
    {
        return $this->numCleaned;
    }
}
