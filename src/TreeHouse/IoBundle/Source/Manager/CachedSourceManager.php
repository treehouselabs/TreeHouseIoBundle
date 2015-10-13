<?php

namespace TreeHouse\IoBundle\Source\Manager;

use TreeHouse\IoBundle\Entity\Feed;
use TreeHouse\IoBundle\Entity\Scraper;
use TreeHouse\IoBundle\Model\SourceInterface;
use TreeHouse\IoBundle\Source\SourceManagerInterface;

/**
 * Wrapper around the main source manger implementation. This sourcemanager is used
 * during imports/scrapes and has one main purpose besides the regular implementation:
 * to cache sources and touch the visited timestamp of a source when it's searched.
 *
 * This is necessary because during imports/scrapes we want to keep track of sources
 * that we encounter, but not necessarily handle or modify.
 */
class CachedSourceManager implements SourceManagerInterface
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
    public function findSourceByFeed(Feed $feed, $originalId)
    {
        if (null === $source = $this->findCachedByFeed($feed, $originalId)) {
            $source = $this->sourceManager->findSourceByFeed($feed, $originalId);
            $this->cache($source);
        }

        return $source;
    }

    /**
     * @inheritdoc
     */
    public function findSourceByScraper(Scraper $scraper, $originalId)
    {
        if (null === $source = $this->findCachedByScraper($scraper, $originalId)) {
            $source = $this->sourceManager->findSourceByScraper($scraper, $originalId);
            $this->cache($source);
        }

        return $source;
    }

    /**
     * @inheritdoc
     */
    public function findSourceByFeedOrCreate(Feed $feed, $originalId, $originalUrl = null)
    {
        if (null === $source = $this->findCachedByFeed($feed, $originalId)) {
            $source = $this->sourceManager->findSourceByFeedOrCreate($feed, $originalId, $originalUrl);
            $this->cache($source);
        }

        return $source;
    }

    /**
     * @inheritdoc
     */
    public function findSourceByScraperOrCreate(Scraper $scraper, $originalId, $originalUrl)
    {
        if (null === $source = $this->findCachedByScraper($scraper, $originalId)) {
            $source = $this->sourceManager->findSourceByScraperOrCreate($scraper, $originalId, $originalUrl);
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
        $this->sources = [];
        $this->originSources = [];
    }

    /**
     * Adds source to the internal cache.
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

        $hash = null;
        if (null !== $feed = $source->getFeed()) {
            $hash = $this->getFeedHash($feed);
        } elseif (null !== $scraper = $source->getScraper()) {
            $hash = $this->getScraperHash($scraper);
        }

        // must have hash and original id
        if (!$hash || !$source->getOriginalId()) {
            return;
        }

        $this->originSources[$hash][$source->getOriginalId()] = $source;
    }

    /**
     * @param int $sourceId
     *
     * @return SourceInterface|null
     */
    protected function findCachedById($sourceId)
    {
        if (!array_key_exists($sourceId, $this->sources)) {
            return null;
        }

        return $this->sources[$sourceId];
    }

    /**
     * @param string $hash
     * @param string $originalId
     *
     * @return SourceInterface|null
     */
    protected function findCachedByOrigin($hash, $originalId)
    {
        // create origin cache if necessary
        if (!isset($this->originSources[$hash])) {
            $this->originSources[$hash] = [];
        }

        // see if we have a cached mapping, return the cached entry
        if (!array_key_exists($originalId, $this->originSources[$hash])) {
            return null;
        }

        return $this->originSources[$hash][$originalId];
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

        return $this->findCachedByOrigin($hash, $originalId);
    }

    /**
     * @param Scraper $scraper
     * @param string  $originalId
     *
     * @return SourceInterface|null
     */
    protected function findCachedByScraper(Scraper $scraper, $originalId)
    {
        $hash = $this->getScraperHash($scraper);

        return $this->findCachedByOrigin($hash, $originalId);
    }

    /**
     * Returns a unique hash for a feed.
     *
     * @param Feed $feed
     *
     * @return string
     */
    protected function getFeedHash(Feed $feed)
    {
        return md5('feed' . $feed->getId());
    }

    /**
     * Returns a unique hash for a scraper.
     *
     * @param Scraper $scraper
     *
     * @return string
     */
    protected function getScraperHash(Scraper $scraper)
    {
        return md5('scraper' . $scraper->getId());
    }
}
