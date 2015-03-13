<?php

namespace TreeHouse\IoBundle\Import\Exception;

use TreeHouse\IoBundle\Model\SourceInterface;

class FailedItemException extends \RuntimeException
{
    /**
     * @var SourceInterface
     */
    protected $source;

    /**
     * @param SourceInterface $source
     * @param string          $message
     * @param integer         $code
     * @param \Exception      $previous
     */
    public function __construct(SourceInterface $source, $message = null, $code = 0, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->source = $source;
    }
}
