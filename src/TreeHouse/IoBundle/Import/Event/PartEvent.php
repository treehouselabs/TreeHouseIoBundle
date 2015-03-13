<?php

namespace TreeHouse\IoBundle\Import\Event;

use Symfony\Component\EventDispatcher\Event;
use TreeHouse\IoBundle\Entity\ImportPart;

class PartEvent extends Event
{
    /**
     * @var ImportPart
     */
    protected $part;

    /**
     * @param ImportPart $part
     */
    public function __construct(ImportPart $part)
    {
        $this->part = $part;
    }

    /**
     * @return ImportPart
     */
    public function getPart()
    {
        return $this->part;
    }
}
