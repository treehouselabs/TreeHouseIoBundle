<?php

namespace TreeHouse\IoBundle\Import\Event;

use Symfony\Component\EventDispatcher\Event;
use TreeHouse\IoBundle\Import\Importer\Importer;

class ExceptionEvent extends Event
{
    /**
     * @var Importer
     */
    protected $importer;

    /**
     * @var \Exception
     */
    protected $exception;

    /**
     * @param Importer   $importer
     * @param \Exception $exception
     */
    public function __construct(Importer $importer, \Exception $exception)
    {
        $this->importer = $importer;
        $this->exception = $exception;
    }

    /**
     * @return Importer
     */
    public function getImporter()
    {
        return $this->importer;
    }

    /**
     * @return \Exception
     */
    public function getException()
    {
        return $this->exception;
    }
}
