<?php

namespace TreeHouse\IoBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use TreeHouse\IoBundle\Model\SourceInterface;

class SourceEvent extends Event
{
    /**
     * @var SourceInterface
     */
    protected $source;

    /**
     * @param SourceInterface $source
     */
    public function __construct(SourceInterface $source)
    {
        $this->source = $source;
    }

    /**
     * @return SourceInterface
     */
    public function getSource()
    {
        return $this->source;
    }
}
