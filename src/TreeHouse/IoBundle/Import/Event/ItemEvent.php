<?php

namespace TreeHouse\IoBundle\Import\Event;

use Symfony\Component\EventDispatcher\Event;
use TreeHouse\IoBundle\Import\Importer\Importer;
use TreeHouse\IoBundle\Item\ItemBag;

class ItemEvent extends Event
{
    /**
     * @var Importer
     */
    protected $importer;

    /**
     * @var ItemBag
     */
    protected $item;

    /**
     * @param Importer $importer The importer where the event originated
     * @param ItemBag  $item     The item
     */
    public function __construct(Importer $importer, ItemBag $item)
    {
        $this->importer = $importer;
        $this->item     = $item;
    }

    /**
     * @return Importer
     */
    public function getImporter()
    {
        return $this->importer;
    }

    /**
     * @return ItemBag
     */
    public function getItem()
    {
        return $this->item;
    }
}
