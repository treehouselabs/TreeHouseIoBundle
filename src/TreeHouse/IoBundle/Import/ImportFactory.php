<?php

namespace TreeHouse\IoBundle\Import;

use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use TreeHouse\Feeder\Exception\ReadException;
use TreeHouse\Feeder\Feed;
use TreeHouse\Feeder\FeedEvents;
use TreeHouse\Feeder\Reader\ReaderInterface;
use TreeHouse\IoBundle\Entity\Feed as FeedEntity;
use TreeHouse\IoBundle\Entity\Import;
use TreeHouse\IoBundle\Entity\ImportPart;
use TreeHouse\IoBundle\Entity\ImportRepository;
use TreeHouse\IoBundle\Exception\UnfinishedImportException;
use TreeHouse\IoBundle\Import\Event\ExceptionEvent;
use TreeHouse\IoBundle\Import\Event\PartEvent;
use TreeHouse\IoBundle\Import\Feed\FeedBuilder;
use TreeHouse\IoBundle\Import\Feed\TransportFactory;
use TreeHouse\IoBundle\Import\Handler\HandlerInterface;
use TreeHouse\IoBundle\Import\Importer\Importer;
use TreeHouse\IoBundle\Import\Importer\ImporterBuilderFactory;
use TreeHouse\IoBundle\Import\Log\ItemLoggerInterface;
use TreeHouse\IoBundle\Import\Processor\ProcessorInterface;
use TreeHouse\IoBundle\Import\Reader\ReaderBuilderFactory;
use TreeHouse\IoBundle\Import\Reader\ReaderBuilderInterface;

class ImportFactory implements EventSubscriberInterface
{
    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    /**
     * @var ImportRegistry
     */
    protected $importRegistry;

    /**
     * @var ImporterBuilderFactory
     */
    protected $importerBuilderFactory;

    /**
     * @var ReaderBuilderFactory
     */
    protected $readerBuilderFactory;

    /**
     * @var ImportStorage
     */
    protected $importStorage;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @param ManagerRegistry          $doctrine
     * @param ImportRegistry           $importRegistry
     * @param ImporterBuilderFactory   $importerBuilderFactory
     * @param ReaderBuilderFactory     $readerBuilderFactory
     * @param ImportStorage            $importStorage
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(
        ManagerRegistry $doctrine,
        ImportRegistry $importRegistry,
        ImporterBuilderFactory $importerBuilderFactory,
        ReaderBuilderFactory $readerBuilderFactory,
        ImportStorage $importStorage,
        EventDispatcherInterface $dispatcher
    ) {
        $this->doctrine = $doctrine;
        $this->importRegistry = $importRegistry;
        $this->importerBuilderFactory = $importerBuilderFactory;
        $this->readerBuilderFactory = $readerBuilderFactory;
        $this->importStorage = $importStorage;
        $this->eventDispatcher = $dispatcher;
    }

    /**
     * @return EventDispatcherInterface
     */
    public function getEventDispatcher()
    {
        return $this->eventDispatcher;
    }

    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents()
    {
        $events = [];

        foreach ([FeedEvents::class, ImportEvents::class] as $class) {
            $refl = new \ReflectionClass($class);
            foreach ($refl->getConstants() as $constant) {
                $events[$constant][] = 'relayEvent';
            }
        }

        $events[ImportEvents::EXCEPTION][] = 'onException';

        return $events;
    }

    /**
     * Relays an event to the main dispatcher in the manager.
     * This is done so listeners can subscribe to this class,
     * while each importer starts with a new dispatcher.
     
     * @param Event  $event
     * @param string $name
     */
    public function relayEvent(Event $event, $name)
    {
        $this->eventDispatcher->dispatch($name, $event);
    }

    /**
     * Handler for an exception event. Importer types can listen to the same
     * event and stop propagation if they want to change this behaviour.
     *
     * @param ExceptionEvent $event
     *
     * @throws \RuntimeException
     */
    public function onException(ExceptionEvent $event)
    {
        $exception = $event->getException();
        if ($exception instanceof ReadException) {
            $msg = sprintf('Error reading feed: %s', $exception->getMessage());
        } else {
            $msg = sprintf(
                'Import aborted with %s: "%s" Stack trace: %s',
                get_class($exception),
                $exception->getMessage(),
                $exception->getTraceAsString()
            );
        }

        throw new \RuntimeException($msg, 0, $exception);
    }

    /**
     * @param ItemLoggerInterface $logger
     */
    public function setItemLogger(ItemLoggerInterface $logger)
    {
        $this->eventDispatcher->addSubscriber($logger);
    }

    /**
     * Creates an import for a feed. If an import for this feed was created
     * before, but has not started yet, that import is returned. All other open
     * imports are closed first.
     *
     * @param FeedEntity $feed         The feed to create the import for
     * @param \DateTime  $scheduleDate The date this import should start
     * @param bool       $forced       Whether to handle items that would normally be skipped
     * @param bool       $partial      If left out, it will be determined based on feed
     *
     * @throws UnfinishedImportException When an existing (and running) import is found
     *
     * @return Import
     */
    public function createImport(FeedEntity $feed, \DateTime $scheduleDate = null, $forced = false, $partial = null)
    {
        if (is_null($scheduleDate)) {
            $scheduleDate = new \DateTime();
        }

        if (is_null($partial)) {
            $partial = $feed->isPartial();
        }
        // see if any imports are still unfinished
        $import = $this->findOrCreateImport($feed);

        $exception = null;

        // check if it's a new import
        if (!$import->getId()) {
            $import->setForced($forced);
            $import->setPartial($partial);
            $import->setDatetimeScheduled($scheduleDate);

            // save now: we want the import on record before starting it
            $this->getRepository()->save($import);

            // add parts
            try {
                $this->addImportParts($import);
            } catch (\Exception $e) {
                $exception = $e;
            }
        }

        // finish import right away if we have no parts, without parts it would never be started
        if ($import->getParts()->count() === 0) {
            $this->getRepository()->startImport($import);
            $this->getRepository()->finishImport($import);
        }

        if ($exception) {
            throw $exception;
        }

        return $import;
    }

    /**
     * @param ImportPart               $part
     * @param EventDispatcherInterface $dispatcher
     *
     * @return ImportJob
     */
    public function createImportJob(ImportPart $part, EventDispatcherInterface $dispatcher = null)
    {
        $import = $part->getImport();
        $feed = $import->getFeed();
        $dispatcher = $dispatcher ?: $this->createEventDispatcher();

        $importer = $this->createImporter($import, $dispatcher, $this->getDefaultImporterOptions($import));
        $reader = $this->createImportPartReader($part, $dispatcher, $this->getDefaultReaderOptions($import));
        $feed = $this->createFeed($feed, $reader, $dispatcher, $this->getDefaultFeedOptions($import));
        $processor = $this->getImportProcessor($import);

        return new ImportJob($part, $feed, $processor, $importer, $this->getRepository());
    }

    /**
     * @param Import                   $import
     * @param EventDispatcherInterface $dispatcher
     * @param array                    $options
     *
     * @return Importer
     */
    protected function createImporter(Import $import, EventDispatcherInterface $dispatcher, array $options = [])
    {
        $type = $this->importRegistry->getImporterType($import->getFeed()->getImporterType());
        $handler = $this->getImportHandler($import);
        $options = array_merge($options, $import->getFeed()->getImporterOptions());

        $builder = $this->importerBuilderFactory->create($dispatcher);

        return $builder->build($type, $import, $handler, $options);
    }

    /**
     * @param Import    $import
     * @param array     $transport
     * @param \DateTime $scheduleDate
     * @param int       $position
     *
     * @return ImportPart
     */
    protected function createImportPart(Import $import, array $transport, \DateTime $scheduleDate = null, $position = null)
    {
        if (is_null($scheduleDate)) {
            $scheduleDate = new \DateTime();
        }

        if (is_null($position)) {
            $positions = $import
                ->getParts()
                ->map(function (ImportPart $part) {
                    return $part->getPosition();
                })
                ->toArray()
            ;

            // add this to ensure we have at least 1 position
            $positions[] = 0;

            $position = max($positions) + 1;
        }

        $part = new ImportPart();
        $part->setPosition($position);
        $part->setTransportConfig($transport);
        $part->setDatetimeScheduled($scheduleDate);
        $part->setImport($import);
        $import->addPart($part);

        $this->getRepository()->savePart($part);

        return $part;
    }

    /**
     * @param Import                   $import
     * @param array                    $transport
     * @param string                   $resourceType
     * @param EventDispatcherInterface $dispatcher
     * @param array                    $options
     *
     * @return ReaderInterface
     */
    protected function createReader(Import $import, array $transport, $resourceType, EventDispatcherInterface $dispatcher, array $options = [])
    {
        $destinationDir = $this->importStorage->getImportDir($import);

        $feed = $import->getFeed();
        $type = $this->importRegistry->getReaderType($feed->getReaderType());
        $builder = $this->readerBuilderFactory->create($dispatcher, $destinationDir);
        $options = array_merge($options, $feed->getReaderOptions());

        return $builder->build($type, $transport, $resourceType, $options);
    }

    /**
     * @param Import                   $import
     * @param EventDispatcherInterface $dispatcher
     * @param array                    $options
     *
     * @return ReaderInterface
     */
    protected function createImportReader(Import $import, EventDispatcherInterface $dispatcher, array $options = [])
    {
        $feed = $import->getFeed();
        $transport = $feed->getTransportConfig();
        $resourceType = ReaderBuilderInterface::RESOURCE_TYPE_MAIN;

        return $this->createReader($import, $transport, $resourceType, $dispatcher, $options);
    }

    /**
     * @param ImportPart               $importPart
     * @param EventDispatcherInterface $dispatcher
     * @param array                    $options
     *
     * @return ReaderInterface
     */
    protected function createImportPartReader(ImportPart $importPart, EventDispatcherInterface $dispatcher, array $options = [])
    {
        $import = $importPart->getImport();
        $transport = $importPart->getTransportConfig();
        $resourceType = ReaderBuilderInterface::RESOURCE_TYPE_PART;

        return $this->createReader($import, $transport, $resourceType, $dispatcher, $options);
    }

    /**
     * @param FeedEntity               $feed
     * @param ReaderInterface          $reader
     * @param EventDispatcherInterface $dispatcher
     * @param array                    $options
     *
     * @return Feed
     */
    protected function createFeed(FeedEntity $feed, ReaderInterface $reader, EventDispatcherInterface $dispatcher, array $options = [])
    {
        $builder = new FeedBuilder($dispatcher);
        $type = $this->importRegistry->getFeedType($feed->getType());
        $options = array_merge($options, $feed->getOptions());

        return $builder->build($type, $reader, $options);
    }

    /**
     * @return EventDispatcherInterface
     */
    protected function createEventDispatcher()
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber($this);

        return $dispatcher;
    }

    /**
     * @param FeedEntity $feed
     *
     * @throws UnfinishedImportException
     *
     * @return Import
     */
    protected function findOrCreateImport(FeedEntity $feed)
    {
        /** @var $imports Import[] */
        $imports = $this->getRepository()->findBy(['feed' => $feed]);

        foreach ($imports as $import) {
            // skip finished imports
            if ($import->isFinished()) {
                continue;
            }

            // if it hasn't started yet, use this one
            if (!$import->isStarted()) {
                return $import;
            }

            try {
                $this->getRepository()->finishImport($import);
            } catch (UnfinishedImportException $e) {
                throw new UnfinishedImportException(
                    $import,
                    sprintf(
                        'Import %d has unfinished parts, close those first before creating a new import',
                        $import->getId()
                    )
                );
            }
        }

        // all previous imports are checked and finished if necessary, we can create a new one now
        $import = new Import();
        $import->setFeed($feed);

        return $import;
    }

    protected function addImportParts(Import $import)
    {
        $dispatcher = $this->createEventDispatcher();
        $options = $this->getDefaultReaderOptions($import);

        $reader = $this->createImportReader($import, $dispatcher, $options);

        foreach ($reader->getResources() as $resource) {
            $transport = TransportFactory::createConfigFromTransport($resource->getTransport());

            $part = $this->createImportPart($import, $transport);
            $this->eventDispatcher->dispatch(ImportEvents::PART_CREATED, new PartEvent($part));
        }
    }

    /**
     * @return ImportRepository
     */
    protected function getRepository()
    {
        return $this->doctrine->getRepository('TreeHouseIoBundle:Import');
    }

    /**
     * @param Import $import
     *
     * @return HandlerInterface
     */
    protected function getImportHandler(Import $import)
    {
        return $this->importRegistry->getHandler('doctrine');
    }

    /**
     * @param Import $import
     *
     * @return ProcessorInterface
     */
    protected function getImportProcessor(Import $import)
    {
        return $this->importRegistry->getProcessor('posix');
    }

    /**
     * Returns default options to pass to the feed builder.
     *
     * @param Import $import
     *
     * @return array
     */
    protected function getDefaultFeedOptions(Import $import)
    {
        return [
            'forced' => $import->isForced(),
            'feed' => $import->getFeed(),
            'default_values' => $import->getFeed()->getDefaultValues(),
        ];
    }

    /**
     * Returns default options to pass to the reader builder.
     *
     * @param Import $import
     *
     * @return array
     */
    protected function getDefaultReaderOptions(Import $import)
    {
        return [
            'partial' => $import->isPartial(),
            'forced' => $import->isForced(),
        ];
    }

    /**
     * Returns default options to pass to the importer builder.
     *
     * @param Import $import
     *
     * @return array
     */
    protected function getDefaultImporterOptions(Import $import)
    {
        return [];
    }
}
