<?php

namespace TreeHouse\IoBundle\Import\Processor;

use TreeHouse\IoBundle\Entity\ImportPart;
use TreeHouse\IoBundle\Import\Exception\RunningPartException;

interface ProcessorInterface
{
    /**
     * Checks whether the part is being run right now.
     *
     * @param ImportPart $part
     *
     * @return bool
     */
    public function isRunning(ImportPart $part);

    /**
     * @param ImportPart $part
     *
     * @throws RunningPartException If the part is being run by another handler
     */
    public function checkProcessing(ImportPart $part);

    /**
     * @param ImportPart $part
     */
    public function markProcessing(ImportPart $part);
}
