<?php

namespace TreeHouse\IoBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use TreeHouse\IoBundle\Export\FeedType\FeedTypeInterface;

class ExportFeedEvent extends Event
{
    /**
     * @var string
     */
    protected $file;

    /**
     * @var integer
     */
    protected $total;

    /**
     * @var FeedTypeInterface
     */
    protected $type;

    /**
     * @param string           $file
     * @param FeedTypeInterface $type
     * @param integer          $total
     */
    public function __construct($file, FeedTypeInterface $type, $total)
    {
        $this->file = $file;
        $this->type = $type;
        $this->total = $total;
    }

    /**
     * @return string
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @return integer
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * @return FeedTypeInterface
     */
    public function getType()
    {
        return $this->type;
    }
}
