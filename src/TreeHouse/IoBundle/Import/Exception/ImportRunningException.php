<?php

namespace TreeHouse\IoBundle\Import\Exception;

use TreeHouse\IoBundle\Entity\Import;

class ImportRunningException extends \RuntimeException
{
    /**
     * @var Import
     */
    protected $import;

    /**
     * @param Import     $import
     * @param string     $message
     * @param integer    $code
     * @param \Exception $previous
     */
    public function __construct(Import $import, $message = null, $code = 0, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->import = $import;
    }
}
