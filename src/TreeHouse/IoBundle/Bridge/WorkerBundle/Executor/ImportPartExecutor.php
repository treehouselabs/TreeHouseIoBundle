<?php

namespace TreeHouse\IoBundle\Bridge\WorkerBundle\Executor;

use Doctrine\Common\Persistence\ManagerRegistry;
use FM\WorkerBundle\Monolog\LoggerAggregate;
use FM\WorkerBundle\Queue\JobExecutor;
use FM\WorkerBundle\Queue\ObjectPayloadInterface;
use Psr\Log\LoggerInterface;
use TreeHouse\IoBundle\Entity\ImportPart;
use TreeHouse\IoBundle\Import\ImportFactory;

/**
 * Performs import of a single ImportPart
 */
class ImportPartExecutor extends JobExecutor implements LoggerAggregate, ObjectPayloadInterface
{
    const NAME = 'import.part';

    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    /**
     * @var ImportFactory
     */
    protected $importFactory;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param ManagerRegistry $doctrine
     * @param ImportFactory   $importFactory
     * @param LoggerInterface $logger
     */
    public function __construct(ManagerRegistry $doctrine, ImportFactory $importFactory, LoggerInterface $logger)
    {
        $this->doctrine      = $doctrine;
        $this->importFactory = $importFactory;
        $this->logger        = $logger;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @inheritdoc
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @inheritdoc
     */
    public function getObjectPayload($object)
    {
        return [$object->getId()];
    }

    /**
     * @inheritdoc
     */
    public function supportsObject($object)
    {
        return $object instanceof ImportPart;
    }

    /**
     * @inheritdoc
     */
    public function execute(array $payload)
    {
        list($partId) = $payload;

        if (null === $part = $this->findImportPart($partId)) {
            $this->getLogger()->warning(sprintf('Import part with id "%d" does not exist', $partId));

            return false;
        }

        $import = $part->getImport();
        $feed = $import->getFeed();

        $this->getLogger()->info(
            sprintf(
                'Importing part <comment>%d</comment> of %s-feed for import "%d" for origin "%s"',
                $part->getPosition(),
                $feed->getType(),
                $import->getId(),
                $feed->getOrigin()->getTitle()
            )
        );

        try {
            $job = $this->importFactory->createImportJob($part);
            $job->setLogger($this->logger);
            $job->run();

            return true;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            return false;
        }
    }

    /**
     * @param  integer    $partId
     *
     * @return ImportPart
     */
    protected function findImportPart($partId)
    {
        return $this->doctrine->getRepository('TreeHouseIoBundle:ImportPart')->find($partId);
    }
}
