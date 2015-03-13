<?php

namespace TreeHouse\IoBundle\Import\Event;

use TreeHouse\IoBundle\Import\Feed\FeedItemBag;
use TreeHouse\IoBundle\Import\Importer\Importer;
use TreeHouse\IoBundle\Model\SourceInterface;

class SuccessItemEvent extends ItemEvent
{
    /**
     * @var SourceInterface
     */
    protected $result;

    /**
     * @param Importer        $importer
     * @param FeedItemBag     $item
     * @param SourceInterface $result
     */
    public function __construct(Importer $importer, FeedItemBag $item, SourceInterface $result)
    {
        parent::__construct($importer, $item);

        $this->result = $result;
    }

    /**
     * @param SourceInterface $result The result
     */
    public function setResult($result)
    {
        $this->result = $result;
    }

    /**
     * @return SourceInterface
     */
    public function getResult()
    {
        return $this->result;
    }
}
