<?php

namespace TreeHouse\IoBundle\Import;

use TreeHouse\IoBundle\Import\Feed\Type\FeedTypeInterface;
use TreeHouse\IoBundle\Import\Handler\HandlerInterface;
use TreeHouse\IoBundle\Import\Importer\Type\ImporterTypeInterface;
use TreeHouse\IoBundle\Import\Processor\ProcessorInterface;
use TreeHouse\IoBundle\Import\Reader\Type\ReaderTypeInterface;

class ImportRegistry
{
    /**
     * @var ReaderTypeInterface[]
     */
    private $readerTypes = [];

    /**
     * @var FeedTypeInterface[]
     */
    private $feedTypes = [];

    /**
     * @var ImporterTypeInterface[]
     */
    private $importerTypes = [];

    /**
     * @var HandlerInterface[]
     */
    private $handlers = [];

    /**
     * @var ProcessorInterface[]
     */
    private $processors = [];

    /**
     * @param string $name
     *
     * @throws \OutOfBoundsException If type with the name is registered
     * @return ReaderTypeInterface
     *
     */
    public function getReaderType($name)
    {
        if (!array_key_exists($name, $this->readerTypes)) {
            throw new \OutOfBoundsException(
                sprintf(
                    'Reader type "%s" is not supported. You can add it by creating a service which implements %s, ' .
                    'and tag it with tree_house.io.reader_type',
                    ReaderTypeInterface::class,
                    $name
                )
            );
        }

        return $this->readerTypes[$name];
    }

    /**
     * @return ReaderTypeInterface[]
     */
    public function getReaderTypes()
    {
        return $this->readerTypes;
    }

    /**
     * Registers a reader type.
     *
     * @param ReaderTypeInterface $type
     * @param string              $name
     */
    public function registerReaderType(ReaderTypeInterface $type, $name)
    {
        $this->readerTypes[$name] = $type;
    }

    /**
     * @param string $name
     *
     * @throws \OutOfBoundsException If type with the name is registered
     * @return FeedTypeInterface
     *
     */
    public function getFeedType($name)
    {
        if (!array_key_exists($name, $this->feedTypes)) {
            throw new \OutOfBoundsException(
                sprintf(
                    'Feed type "%s" is not supported. You can add it by creating a service which implements %s, ' .
                    'and tag it with tree_house.io.feed_type',
                    $name,
                    FeedTypeInterface::class
                )
            );
        }

        return $this->feedTypes[$name];
    }

    /**
     * @return FeedTypeInterface[]
     */
    public function getFeedTypes()
    {
        return $this->feedTypes;
    }

    /**
     * Registers a feed type.
     *
     * @param FeedTypeInterface $type
     * @param string            $name
     */
    public function registerFeedType(FeedTypeInterface $type, $name)
    {
        $this->feedTypes[$name] = $type;
    }

    /**
     * @param string $name
     *
     * @throws \OutOfBoundsException If no type with the name is registered
     * @return ImporterTypeInterface
     *
     */
    public function getImporterType($name)
    {
        if (!array_key_exists($name, $this->importerTypes)) {
            throw new \OutOfBoundsException(
                sprintf(
                    'Importer type "%s" is not supported. You can add it by creating a service which implements %s, ' .
                    'and tag it with tree_house.io.importer_type',
                    $name,
                    ImporterTypeInterface::class
                )
            );
        }

        return $this->importerTypes[$name];
    }

    /**
     * @return ImporterTypeInterface[]
     */
    public function getImporterTypes()
    {
        return $this->importerTypes;
    }

    /**
     * Registers an importer type.
     *
     * @param ImporterTypeInterface $type
     * @param string                $name
     */
    public function registerImporterType(ImporterTypeInterface $type, $name)
    {
        $this->importerTypes[$name] = $type;
    }

    /**
     * @param string $name
     *
     * @throws \OutOfBoundsException If no handler with the name is registered
     * @return HandlerInterface
     *
     */
    public function getHandler($name)
    {
        if (!array_key_exists($name, $this->handlers)) {
            throw new \OutOfBoundsException(
                sprintf(
                    'Import handler "%s" is not supported. You can add it by creating a service which implements %s, ' .
                    'and tag it with tree_house.io.import_handler',
                    $name,
                    HandlerInterface::class
                )
            );
        }

        return $this->handlers[$name];
    }

    /**
     * @return HandlerInterface[]
     */
    public function getHandlers()
    {
        return $this->handlers;
    }

    /**
     * Registers a handler.
     *
     * @param HandlerInterface $handler
     * @param string           $name
     */
    public function registerHandler(HandlerInterface $handler, $name)
    {
        $this->handlers[$name] = $handler;
    }

    /**
     * @param string $name
     *
     * @throws \OutOfBoundsException If no processor with the name is registered
     * @return ProcessorInterface
     *
     */
    public function getProcessor($name)
    {
        if (!array_key_exists($name, $this->processors)) {
            throw new \OutOfBoundsException(
                sprintf(
                    'Import processor "%s" is not supported. You can add it by creating a service which implements %s, ' .
                    'and tagging it with tree_house.io.import_processor',
                    $name,
                    ProcessorInterface::class
                )
            );
        }

        return $this->processors[$name];
    }

    /**
     * @return ProcessorInterface[]
     */
    public function getProcessors()
    {
        return $this->processors;
    }

    /**
     * Registers a processor.
     *
     * @param ProcessorInterface $processor
     * @param string             $name
     */
    public function registerProcessor(ProcessorInterface $processor, $name)
    {
        $this->processors[$name] = $processor;
    }
}
