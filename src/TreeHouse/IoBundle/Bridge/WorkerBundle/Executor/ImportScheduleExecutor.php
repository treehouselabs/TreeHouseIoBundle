<?php

namespace TreeHouse\IoBundle\Bridge\WorkerBundle\Executor;

use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidArgumentException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use TreeHouse\IoBundle\Entity\Feed;
use TreeHouse\IoBundle\Import\ImportFactory;
use TreeHouse\IoBundle\Import\ImportScheduler;
use TreeHouse\WorkerBundle\Executor\AbstractExecutor;
use TreeHouse\WorkerBundle\Executor\ObjectPayloadInterface;

/**
 * Schedules an import.
 */
class ImportScheduleExecutor extends AbstractExecutor implements ObjectPayloadInterface
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
        $this->scheduler = $scheduler;
        $this->importFactory = $importFactory;
        $this->logger = $logger;
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
    public function configurePayload(OptionsResolver $resolver)
    {
        $resolver->setRequired(0);
        $resolver->setAllowedTypes(0, 'numeric');
        $resolver->setNormalizer(0, function (Options $options, $value) {
            if (null === $listing = $this->scheduler->findFeed($value)) {
                throw new InvalidArgumentException(sprintf('Feed with id "%d" does not exist', $value));
            }

            return $listing;
        });

        $resolver->setDefaults([1 => false]);
        $resolver->setNormalizer(1, function (Options $options, $value) {
            return (boolean) $value;
        });
    }

    /**
     * @inheritdoc
     */
    public function execute(array $payload)
    {
        /** @var Feed $feed */
        /** @var bool $force */
        list($feed, $force) = $payload;

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
