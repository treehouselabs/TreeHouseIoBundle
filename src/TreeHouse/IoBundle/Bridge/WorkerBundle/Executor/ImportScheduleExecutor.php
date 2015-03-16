<?php

namespace TreeHouse\IoBundle\Bridge\WorkerBundle\Executor;

use FM\WorkerBundle\Monolog\LoggerAggregate;
use FM\WorkerBundle\Queue\JobExecutor;
use FM\WorkerBundle\Queue\ObjectPayloadInterface;
use Psr\Log\LoggerInterface;
use TreeHouse\IoBundle\Entity\Feed;
use TreeHouse\IoBundle\Import\ImportFactory;
use TreeHouse\IoBundle\Import\ImportScheduler;

/**
 * Schedules an import
 */
class ImportScheduleExecutor extends JobExecutor implements LoggerAggregate, ObjectPayloadInterface
{
    const NAME = 'import.schedule';

    /**
     * @var ImportScheduler
     */
    protected $scheduler;

    /**
     * @var ImportFactory
     */
    protected $importFactory;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param ImportScheduler $scheduler
     * @param ImportFactory   $importFactory
     * @param LoggerInterface $logger
     */
    public function __construct(ImportScheduler $scheduler, ImportFactory $importFactory, LoggerInterface $logger)
    {
        $this->scheduler     = $scheduler;
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
        return $object instanceof Feed;
    }

    /**
     * @inheritdoc
     */
    public function execute(array $payload)
    {
        $feedId = array_shift($payload);
        $force  = !empty($payload) ? (boolean) array_shift($payload) : false;

        if (null === $feed = $this->scheduler->findFeed($feedId)) {
            $this->getLogger()->warning(sprintf('Feed with id "%d" does not exist', $feedId));

            return false;
        }

        try {
            $import = $this->importFactory->createImport($feed, new \DateTime(), $force);

            $this->logger->info(sprintf('Created import %d', $import->getId()));

            $this->logger->debug('Scheduling parts');
            foreach ($import->getParts() as $part) {
                $this->scheduler->schedulePart($part);
            }

            return true;
        } catch (\Exception $e) {
            // we could get an exception when a new import cannot be created, for example when an existing import
            // for this feed is still running.
            $this->logger->error(sprintf('<error>%s</error>', $e->getMessage()));

            return false;
        }
    }
}
