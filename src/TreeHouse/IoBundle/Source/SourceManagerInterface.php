<?php

namespace TreeHouse\IoBundle\Source;

use TreeHouse\IoBundle\Entity\Feed;
use TreeHouse\IoBundle\Entity\SourceRepository;
use TreeHouse\IoBundle\Model\SourceInterface;

interface SourceManagerInterface
{
    /**
     * @return SourceRepository
     */
    public function getRepository();

    /**
     * Finds an existing source by id
     *
     * @param integer $sourceId
     *
     * @return SourceInterface
     */
    public function findById($sourceId);

    /**
     * Finds source object for a given original id
     *
     * @param Feed    $feed
     * @param integer $originalId
     *
     * @return SourceInterface
     */
    public function findSource(Feed $feed, $originalId);

    /**
     * Finds source object for a given original id, optionally creates a new one if a source cannot be found
     *
     * @param Feed    $feed
     * @param integer $originalId
     * @param string  $originalUrl
     *
     * @return SourceInterface
     */
    public function findSourceOrCreate(Feed $feed, $originalId, $originalUrl = null);

    /**
     * Persists a (new) source
     *
     * @param SourceInterface $source
     *
     * @return void
     */
    public function persist(SourceInterface $source);

    /**
     * Persists an existing source
     *
     * @param SourceInterface $source
     *
     * @return void
     */
    public function remove(SourceInterface $source);

    /**
     * Detaches a source, making all changes irrelevant
     *
     * @param SourceInterface $source
     *
     * @return void
     */
    public function detach(SourceInterface $source);

    /**
     * Flushes all outstanding changes in sources
     *
     * @param SourceInterface $source
     *
     * @return void
     */
    public function flush(SourceInterface $source = null);

    /**
     * Clears caches
     *
     * @return void
     */
    public function clear();
}
