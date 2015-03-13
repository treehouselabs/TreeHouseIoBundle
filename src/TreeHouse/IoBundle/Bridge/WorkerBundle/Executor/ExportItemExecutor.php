<?php

namespace TreeHouse\IoBundle\Bridge\WorkerBundle\Executor;

use Doctrine\Common\Persistence\ManagerRegistry;
use FM\WorkerBundle\Queue\JobExecutor;
use TreeHouse\IoBundle\Export\FeedExporter;

class ExportItemExecutor extends JobExecutor
{
    const NAME = "export.item";

    /**
     * @var FeedExporter
     */
    protected $exporter;

    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    /**
     * Constructor.
     *
     * @param FeedExporter    $exporter
     * @param ManagerRegistry $doctrine
     */
    public function __construct(FeedExporter $exporter, ManagerRegistry $doctrine)
    {
        $this->exporter = $exporter;
        $this->doctrine = $doctrine;
    }

    /**
     * Executes a job with given payload
     *
     * @param  array $payload
     * @return mixed
     */
    public function execute(array $payload)
    {
        list($entityClass, $entityId) = $payload;

        $entity = $this->doctrine->getRepository($entityClass)->find($entityId);

        if (null === $entity) {
            return false;
        }

        return $this->exporter->cacheItem($entity);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return self::NAME;
    }
}
