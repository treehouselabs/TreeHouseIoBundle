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
     * @return ReaderTypeInterface
     *
     * @throws \OutOfBoundsException If type with the name is registered
     */
    public function getReaderType($name)
    {
        if (!array_key_exists($name, $this->readerTypes)) {
            throw new \OutOfBoundsException(
                sprintf(
                    'Reader type "%s" is not supported. You can add it by creating a service which implements '.
                    'TreeHouse\IoBundle\Import\ReaderType\ReaderTypeInterface, and tag it with io.reader_type',
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
     * Registers a reader type
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
     * @return FeedTypeInterface
     *
     * @throws \OutOfBoundsException If type with the name is registered
     */
    public function getFeedType($name)
    {
        if (!array_key_exists($name, $this->feedTypes)) {
            throw new \OutOfBoundsException(
                sprintf(
                    'Feed type "%s" is not supported. You can add it by creating a service which implements '.
                    'TreeHouse\IoBundle\Import\FeedType\FeedTypeInterface, and tag it with io.feed_type',
                    $name
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
     * Registers a feed type
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
     * @return ImporterTypeInterface
     *
     * @throws \OutOfBoundsException If no type with the name is registered
     */
    public function getImporterType($name)
    {
        if (!array_key_exists($name, $this->importerTypes)) {
            throw new \OutOfBoundsException(
                sprintf(
                    'Importer type "%s" is not supported. You can add it by creating a service which implements '.
                    'TreeHouse\IoBundle\Import\ImporterType\ImporterTypeInterface, and tag it with io.importer_type',
                    $name
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
     * Registers an importer type
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
     * @return HandlerInterface
     *
     * @throws \OutOfBoundsException If no handler with the name is registered
     */
    public function getHandler($name)
    {
        if (!array_key_exists($name, $this->handlers)) {
            throw new \OutOfBoundsException(
                sprintf(
                    'Import handler "%s" is not supported. You can add it by creating a service which implements '.
                    'TreeHouse\IoBundle\Import\Handler\HandlerInterface, and tag it with io.import_handler',
                    $name
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
     * Registers a handler
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
     * @return ProcessorInterface
     *
     * @throws \OutOfBoundsException If no processor with the name is registered
     */
    public function getProcessor($name)
    {
        if (!array_key_exists($name, $this->processors)) {
            throw new \OutOfBoundsException(
                sprintf(
                    'Import processor "%s" is not supported. You can add it by creating a service which implements '.
                    'TreeHouse\IoBundle\Import\Processor\ProcessorInterface, and tagging it with io.import_processor',
                    $name
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
     * Registers a processor
     *
     * @param ProcessorInterface $processor
     * @param string             $name
     */
    public function registerProcessor(ProcessorInterface $processor, $name)
    {
        $this->processors[$name] = $processor;
    }
}
