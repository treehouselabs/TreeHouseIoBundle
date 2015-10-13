<?php

namespace TreeHouse\IoBundle\Import\Event;

use TreeHouse\IoBundle\Import\Feed\FeedItemBag;
use TreeHouse\IoBundle\Import\Importer\Importer;

class InvalidItemEvent extends ItemEvent
{
    /**
     * @var string
     */
    protected $reason;

    /**
     * @param Importer    $importer
     * @param FeedItemBag $item
     * @param string      $reason
     */
    public function __construct(Importer $importer, FeedItemBag $item, $reason)
    {
        parent::__construct($importer, $item);

        $this->reason = $reason;
    }

    /**
     * @param string $reason
     */
    public function setReason($reason)
    {
        $this->reason = $reason;
    }

    /**
     * @return string
     */
    public function getReason()
    {
        return $this->reason;
    }
}
