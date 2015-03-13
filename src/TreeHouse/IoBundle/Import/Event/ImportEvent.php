<?php

namespace TreeHouse\IoBundle\Import\Event;

use Symfony\Component\EventDispatcher\Event;
use TreeHouse\IoBundle\Entity\Import;

class ImportEvent extends Event
{
    /**
     * @var Import
     */
    protected $import;

    /**
     * @param Import $import
     */
    public function __construct(Import $import)
    {
        $this->import = $import;
    }

    /**
     * @return Import
     */
    public function getImport()
    {
        return $this->import;
    }
}
