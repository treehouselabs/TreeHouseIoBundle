<?php

namespace TreeHouse\IoBundle\Source\Cleaner;

use TreeHouse\IoBundle\Source\SourceManagerInterface;

class OrphanedSourceCleaner implements SourceCleanerInterface
{
    /**
     * @var SourceManagerInterface
     */
    protected $sourceManager;

    /**
     * @param SourceManagerInterface $sourceManager
     */
    public function __construct(SourceManagerInterface $sourceManager)
    {
        $this->sourceManager = $sourceManager;
    }

    /**
     * @inheritdoc
     */
    public function clean(DelegatingSourceCleaner $cleaner, ThresholdVoterInterface $voter)
    {
        $builder = $this->sourceManager->getRepository()->queryOrphaned();

        return $cleaner->cleanByQuery($builder->getQuery());
    }
}
