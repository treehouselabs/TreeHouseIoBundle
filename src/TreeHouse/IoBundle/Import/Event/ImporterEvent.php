<?php

namespace TreeHouse\IoBundle\Import\Event;

use Symfony\Component\EventDispatcher\Event;
use TreeHouse\IoBundle\Entity\ImportPart;
use TreeHouse\IoBundle\Import\Importer\Importer;

class ImporterEvent extends Event
{
    /**
     * @var ImportPart
     */
    protected $part;

    /**
     * @var Importer
     */
    protected $importer;

    /**
     * @param ImportPart $part
     * @param Importer   $importer
     */
    public function __construct(ImportPart $part, Importer $importer)
    {
        $this->part = $part;
        $this->importer = $importer;
    }

    /**
     * @return ImportPart
     */
    public function getPart()
    {
        return $this->part;
    }

    /**
     * @return Importer
     */
    public function getImporter()
    {
        return $this->importer;
    }
}
