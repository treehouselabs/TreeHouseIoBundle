<?php

namespace TreeHouse\IoBundle\Origin;

use Doctrine\ORM\EntityRepository;
use TreeHouse\IoBundle\Model\OriginInterface;

interface OriginManagerInterface
{
    /**
     * @return EntityRepository
     */
    public function getRepository();

    /**
     * Finds an existing origin by id.
     *
     * @param int $originId
     *
     * @return OriginInterface
     */
    public function findById($originId);

    /**
     * Persists a (new) origin.
     *
     * @param OriginInterface $origin
     */
    public function persist(OriginInterface $origin);

    /**
     * Persists an existing origin.
     *
     * @param OriginInterface $origin
     */
    public function remove(OriginInterface $origin);

    /**
     * Detaches a origin, making all changes irrelevant.
     *
     * @param OriginInterface $origin
     */
    public function detach(OriginInterface $origin);

    /**
     * Flushes all outstanding changes in origins.
     */
    public function flush();

    /**
     * Clears caches.
     */
    public function clear();
}
