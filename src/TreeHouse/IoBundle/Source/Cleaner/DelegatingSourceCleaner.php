<?php

namespace TreeHouse\IoBundle\Source\Cleaner;

use Doctrine\ORM\AbstractQuery;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use TreeHouse\IoBundle\Event\SourceEvent;
use TreeHouse\IoBundle\IoEvents;
use TreeHouse\IoBundle\Model\SourceInterface;
use TreeHouse\IoBundle\Source\SourceManagerInterface;

/**
 * Keeps a collection of source cleaner and delegates to the cleaners that support the given source.
 *
 * You can add your own cleaners by tagging your service with the "io.source_cleaner" tag.
 */
class DelegatingSourceCleaner
{
    /**
     * @var SourceManagerInterface
     */
    protected $sourceManager;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var SourceCleanerInterface[]
     */
    protected $cleaners = [];

    /**
     * @param SourceManagerInterface   $sourceManager
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(SourceManagerInterface $sourceManager, EventDispatcherInterface $eventDispatcher)
    {
        $this->sourceManager = $sourceManager;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @return EventDispatcherInterface
     */
    public function getEventDispatcher()
    {
        return $this->eventDispatcher;
    }

    /**
     * Registers a cleaner.
     *
     * @param SourceCleanerInterface $cleaner
     */
    public function registerCleaner(SourceCleanerInterface $cleaner)
    {
        $this->cleaners[] = $cleaner;
    }

    /**
     * @return SourceCleanerInterface[]
     */
    public function getCleaners()
    {
        return $this->cleaners;
    }

    /**
     * @inheritdoc
     */
    public function cleanAll(ThresholdVoterInterface $voter = null)
    {
        if (null === $voter) {
            $voter = new ThresholdVoter(function () { return false; }, $this->eventDispatcher);
        }

        $numCleaned = 0;

        foreach ($this->cleaners as $cleaners) {
            $numCleaned += $cleaners->clean($this, $voter);
        }

        return $numCleaned;
    }

    /**
     * @param AbstractQuery $query
     *
     * @throws \LogicException
     * @return int
     *
     */
    public function cleanByQuery(AbstractQuery $query)
    {
        $numCleaned = 0;

        /** @var SourceInterface $source */
        foreach ($query->iterate() as list($source)) {
            if (!$source instanceof SourceInterface) {
                throw new \LogicException(
                    sprintf(
                        'Invalid iterator given, encountered %s instead of SourceInterface',
                        is_object($source) ? get_class($source) : gettype($source)
                    )
                );
            }

            $this->eventDispatcher->dispatch(IoEvents::PRE_CLEAN_SOURCE, new SourceEvent($source));
            $this->sourceManager->remove($source);
            $this->eventDispatcher->dispatch(IoEvents::POST_CLEAN_SOURCE, new SourceEvent($source));

            ++$numCleaned;

            if ($numCleaned % 50 === 0) {
                $this->sourceManager->flush();
                $this->sourceManager->clear();
            }
        }

        if ($numCleaned > 0) {
            $this->sourceManager->flush();
            $this->sourceManager->clear();
        }

        return $numCleaned;
    }
}
