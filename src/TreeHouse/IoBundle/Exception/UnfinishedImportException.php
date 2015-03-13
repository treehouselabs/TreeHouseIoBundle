<?php

namespace TreeHouse\IoBundle\Exception;

use TreeHouse\IoBundle\Entity\Import;

class UnfinishedImportException extends \Exception
{
    /**
     * @var Import
     */
    protected $import;

    /**
     * @inheritdoc
     *
     * @param Import $import
     */
    public function __construct(Import $import, $message = null, $code = 0, \Exception $previous = null)
    {
        $message = $message ?: 'Cannot close import until all parts have finished';

        parent::__construct($message, $code, $previous);

        $this->import = $import;
    }

    /**
     * @param Import $import
     *
     * @return static
     */
    public static function create(Import $import)
    {
        return new static($import);
    }

    /**
     * @return Import
     */
    public function getImport()
    {
        return $this->import;
    }
}
