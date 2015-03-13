<?php

namespace TreeHouse\IoBundle\Import\Importer;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ImporterBuilderFactory
{
    /**
     * @param EventDispatcherInterface $dispatcher
     *
     * @return ImporterBuilderInterface
     */
    public function create(EventDispatcherInterface $dispatcher)
    {
        return new ImporterBuilder($dispatcher);
    }
}
