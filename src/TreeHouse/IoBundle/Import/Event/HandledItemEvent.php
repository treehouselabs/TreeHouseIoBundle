<?php

namespace TreeHouse\IoBundle\Import\Event;

use TreeHouse\IoBundle\Import\Feed\FeedItemBag;
use TreeHouse\IoBundle\Import\Importer\Importer;
use TreeHouse\IoBundle\Model\SourceInterface;

class HandledItemEvent extends ItemEvent
{
    /**
     * @var SourceInterface
     */
    protected $source;

    /**
     * @param Importer        $importer
     * @param FeedItemBag     $item
     * @param SourceInterface $source
     */
    public function __construct(Importer $importer, FeedItemBag $item, SourceInterface $source)
    {
        parent::__construct($importer, $item);

        $this->source = $source;
    }

    /**
     * @param SourceInterface $source The result
     */
    public function setSource($source)
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
