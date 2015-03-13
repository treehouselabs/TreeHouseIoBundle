<?php

namespace TreeHouse\IoBundle\Import\Importer;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use TreeHouse\IoBundle\Entity\Import;
use TreeHouse\IoBundle\Import\Handler\HandlerInterface;
use TreeHouse\IoBundle\Import\Importer\Type\ImporterTypeInterface;

interface ImporterBuilderInterface
{
    /**
     * @param ImporterTypeInterface $type
     * @param Import                $import
     * @param HandlerInterface      $handler
     * @param array                 $options
     *
     * @return Importer
     */
    public function build(ImporterTypeInterface $type, Import $import, HandlerInterface $handler, array $options);

    /**
     * @return EventDispatcherInterface
     */
    public function getEventDispatcher();
}
