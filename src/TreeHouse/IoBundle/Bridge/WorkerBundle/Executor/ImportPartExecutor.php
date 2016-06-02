<?php

namespace TreeHouse\IoBundle\Bridge\WorkerBundle\Executor;

use Doctrine\Common\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidArgumentException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use TreeHouse\IoBundle\Entity\ImportPart;
use TreeHouse\IoBundle\Import\ImportFactory;
use TreeHouse\WorkerBundle\Executor\AbstractExecutor;
use TreeHouse\WorkerBundle\Executor\ObjectPayloadInterface;

/**
 * Performs import of a single ImportPart.
 */
class ImportPartExecutor extends AbstractExecutor implements ObjectPayloadInterface
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
        $this->doctrine = $doctrine;
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
        return $object instanceof ImportPart;
    }

    /**
     * @inheritdoc
     */
    public function configurePayload(OptionsResolver $resolver)
    {
        $resolver->setRequired(0);
        $resolver->setAllowedTypes(0, 'numeric');
        $resolver->setNormalizer(0, function (Options $options, $value) {
            if (null === $part = $this->findImportPart($value)) {
                throw new InvalidArgumentException(sprintf('Import part with id "%d" does not exist', $value));
            }

            return $part;
        });
    }

    /**
     * @inheritdoc
     */
    public function execute(array $payload)
    {
        /** @var ImportPart $part */
        list($part) = $payload;

        $import = $part->getImport();
        $feed = $import->getFeed();

        $this->logger->info(
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
     * @param int $partId
     *
     * @return ImportPart
     */
    protected function findImportPart($partId)
    {
        return $this->doctrine->getRepository('TreeHouseIoBundle:ImportPart')->find($partId);
    }
}
