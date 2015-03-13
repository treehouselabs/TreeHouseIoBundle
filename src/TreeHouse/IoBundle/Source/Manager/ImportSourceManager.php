<?php

namespace TreeHouse\IoBundle\Source\Manager;

use TreeHouse\IoBundle\Entity\Feed;
use TreeHouse\IoBundle\Model\SourceInterface;
use TreeHouse\IoBundle\Source\SourceManagerInterface;

/**
 * Wrapper around the main source manger implementation. This sourcemanager is used
 * during imports and has one main purpose besides the regular implementation: to
 * cache sources and touch the visited timestamp of a source when it's searched.
 *
 * This is necessary because during imports we want to keep track of sources that
 * we encounter, but not necessarily handle or modify.
 */
class ImportSourceManager implements SourceManagerInterface
{
    /**
     * @var SourceManagerInterface
     */
    protected $sourceManager;

    /**
     * @var array
     */
    protected $sources = [];

    /**
     * @var array
     */
    protected $originSources = [];

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
    public function getRepository()
    {
        return $this->sourceManager->getRepository();
    }

    /**
     * @inheritdoc
     */
    public function findById($sourceId)
    {
        if (null === $source = $this->findCachedById($sourceId)) {
            $source = $this->sourceManager->findById($sourceId);
            $this->cache($source);
        }

        return $source;
    }

    /**
     * @inheritdoc
     */
    public function findSource(Feed $feed, $originalId)
    {
        if (null === $source = $this->findCachedByFeed($feed, $originalId)) {
            $source = $this->sourceManager->findSource($feed, $originalId);
            $this->cache($source);
        }

        return $source;
    }

    /**
     * @inheritdoc
     */
    public function findSourceOrCreate(Feed $feed, $originalId, $originalUrl = null)
    {
        if (null === $source = $this->findCachedByFeed($feed, $originalId)) {
            $source = $this->sourceManager->findSourceOrCreate($feed, $originalId, $originalUrl);
            $this->cache($source);
        }

        return $source;
    }

    /**
     * @inheritdoc
     */
    public function persist(SourceInterface $source)
    {
        $this->sourceManager->persist($source);
    }

    /**
     * @inheritdoc
     */
    public function remove(SourceInterface $source)
    {
        $this->sourceManager->remove($source);
    }

    /**
     * @inheritdoc
     */
    public function detach(SourceInterface $source)
    {
        $this->sourceManager->detach($source);
    }

    /**
     * @inheritdoc
     */
    public function flush(SourceInterface $source = null)
    {
        $this->sourceManager->flush($source);
    }

    /**
     * @inheritdoc
     */
    public function clear()
    {
        $this->sourceManager->clear();
        $this->sources       = [];
        $this->originSources = [];
    }

    /**
     * Adds source to the internal cache
     *
     * @param SourceInterface $source
     */
    protected function cache(SourceInterface $source = null)
    {
        if (null === $source) {
            return;
        }

        // mark as visited
        $source->setDatetimeLastVisited(new \DateTime());

        // cache by id
        if ($source->getId()) {
            $this->sources[$source->getId()] = $source;
        }

        // cache by origin
        if ((null === ($feed = $source->getFeed())) || !$source->getOriginalId()) {
            return;
        }

        $hash       = $this->getFeedHash($feed);
        $originalId = $source->getOriginalId();

        $this->originSources[$hash][$originalId] = $source;
    }

    /**
     * @param integer $sourceId
     *
     * @return SourceInterface|null
     */
    protected function findCachedById($sourceId)
    {
        if (!array_key_exists($sourceId, $this->sources)) {
            return;
        }

        return $this->sources[$sourceId];
    }

    /**
     * @param Feed   $feed
     * @param string $originalId
     *
     * @return SourceInterface|null
     */
    protected function findCachedByFeed(Feed $feed, $originalId)
    {
        $hash = $this->getFeedHash($feed);

        // create origin cache if necessary
        if (!isset($this->originSources[$hash])) {
            $this->originSources[$hash] = [];
        }

        // see if we have a cached mapping
        // return the cached entry
        if (!array_key_exists($originalId, $this->originSources[$hash])) {
            return;
        }

        return $this->originSources[$hash][$originalId];
    }

    /**
     * Returns a unique hash for an origin and a feed
     *
     * @param  Feed   $feed
     * @return string
     */
    protected function getFeedHash(Feed $feed)
    {
        return md5($feed->getId());
    }
}
