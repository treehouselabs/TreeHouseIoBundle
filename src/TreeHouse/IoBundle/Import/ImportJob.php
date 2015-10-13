<?php

namespace TreeHouse\IoBundle\Import;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use TreeHouse\Feeder\Feed;
use TreeHouse\IoBundle\Entity\ImportPart;
use TreeHouse\IoBundle\Entity\ImportRepository;
use TreeHouse\IoBundle\Import\Event\ImporterEvent;
use TreeHouse\IoBundle\Import\Event\ImportEvent;
use TreeHouse\IoBundle\Import\Importer\Importer;
use TreeHouse\IoBundle\Import\Processor\ProcessorInterface;

class ImportJob implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var ImportPart
     */
    protected $part;

    /**
     * @var Feed
     */
    protected $feed;

    /**
     * @var ProcessorInterface
     */
    protected $processor;

    /**
     * @var Importer
     */
    protected $importer;

    /**
     * @var ImportRepository
     */
    protected $repository;

    /**
     * @param ImportPart         $part
     * @param Feed               $feed
     * @param ProcessorInterface $processor
     * @param Importer           $importer
     * @param ImportRepository   $repository
     *
     * @throws \RuntimeException When the import or the part has already finished
     */
    public function __construct(
        ImportPart $part,
        Feed $feed,
        ProcessorInterface $processor,
        Importer $importer,
        ImportRepository $repository
    ) {
        $this->feed = $feed;
        $this->part = $part;
        $this->processor = $processor;
        $this->importer = $importer;
        $this->repository = $repository;
        $this->logger = new NullLogger();

        $import = $part->getImport();

        // check if import has already finished
        if ($import->isFinished()) {
            throw new \RuntimeException(sprintf('Import %d has already finished', $import->getId()));
        }

        // check if this part has already finished
        if ($part->isFinished()) {
            throw new \RuntimeException(
                sprintf('Part %d of import %d has already finished', $part->getPosition(), $import->getId())
            );
        }
    }

    /**
     * @return ImportResult
     */
    public function run()
    {
        $import = $this->part->getImport();

        // part is not finished, maybe it's already started/running
        if ($this->part->isStarted()) {
            $this->processor->checkProcessing($this->part);

            $this->logger->warning(
                sprintf(
                    'Part %d of import %d has already started, but the process (%s) is no longer running. ' .
                    'Resuming the part now.',
                    $this->part->getPosition(),
                    $import->getId(),
                    $this->part->getProcess()
                )
            );
        }

        // start import if necessary
        if (!$import->isStarted()) {
            $this->importer->dispatchEvent(ImportEvents::IMPORT_START, new ImportEvent($import));
            $this->repository->startImport($import);
        }

        try {
            $this->start();
            $this->importer->run($this->feed);
        } catch (\Exception $e) {
            // log the error
            $this->logger->error($e->getMessage());
            $this->logger->debug($e->getTraceAsString());

            // check if we have any retries left
            if ($this->part->getRetries() > 0) {
                // mark as unstarted and bail out
                $this->retry();

                return null;
            } else {
                $this->fail($e->getMessage());
            }
        }

        $this->repository->addResult($import, $this->importer->getResult());

        $this->finish();

        return $this->importer->getResult();
    }

    /**
     * Starts the import part.
     */
    protected function start()
    {
        $this->processor->markProcessing($this->part);
        $this->repository->startImportPart($this->part);

        $this->importer->dispatchEvent(ImportEvents::PART_START, new ImporterEvent($this->part, $this->importer));
    }

    /**
     * @throws \RuntimeException When the part has not started yet
     */
    protected function finish()
    {
        $this->logger->debug(
            sprintf(
                'Finishing part %d on position %d for import %d',
                $this->part->getId(),
                $this->part->getPosition(),
                $this->part->getImport()->getId()
            )
        );

        $this->repository->finishImportPart($this->part);

        $this->importer->dispatchEvent(ImportEvents::PART_FINISH, new ImporterEvent($this->part, $this->importer));

        // if this is the last part, end the import also
        $import = $this->part->getImport();

        if (!$this->repository->importHasUnfinishedParts($import)) {
            $this->repository->finishImport($import);
            $this->importer->dispatchEvent(ImportEvents::IMPORT_FINISH, new ImportEvent($import));
        }
    }

    /**
     * @throws \RuntimeException
     */
    protected function retry()
    {
        $retriesLeft = $this->part->getRetries();
        if ($retriesLeft < 1) {
            throw new \RuntimeException('No more retries left!');
        }

        $this->part->setRetries(--$retriesLeft);
        $this->part->setDatetimeEnded(null);
        $this->part->setProcess(null);

        $this->repository->savePart($this->part);
    }

    /**
     * @param string $message
     */
    protected function fail($message)
    {
        // log the error
        $this->part->setError($message);

        $this->logger->error(
            sprintf(
                'Importing part %d of import %d failed with message: %s',
                $this->part->getPosition(),
                $this->part->getImport()->getId(),
                $message
            )
        );
    }
}
