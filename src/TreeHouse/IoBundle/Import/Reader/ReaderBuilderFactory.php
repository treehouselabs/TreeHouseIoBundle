<?php

namespace TreeHouse\IoBundle\Import\Reader;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ReaderBuilderFactory
{
    /**
     * @param EventDispatcherInterface $dispatcher
     * @param string                   $destinationDir
     *
     * @return ReaderBuilderInterface
     */
    public function create(EventDispatcherInterface $dispatcher, $destinationDir)
    {
        return new ReaderBuilder($dispatcher, $destinationDir);
    }
}
