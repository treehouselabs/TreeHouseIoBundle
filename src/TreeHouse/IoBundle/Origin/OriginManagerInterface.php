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
     * Finds an existing origin by id
     *
     * @param integer $originId
     *
     * @return OriginInterface
     */
    public function findById($originId);

    /**
     * Persists a (new) origin
     *
     * @param OriginInterface $origin
     *
     * @return void
     */
    public function persist(OriginInterface $origin);

    /**
     * Persists an existing origin
     *
     * @param OriginInterface $origin
     *
     * @return void
     */
    public function remove(OriginInterface $origin);

    /**
     * Detaches a origin, making all changes irrelevant
     *
     * @param OriginInterface $origin
     *
     * @return void
     */
    public function detach(OriginInterface $origin);

    /**
     * Flushes all outstanding changes in origins
     *
     * @return void
     */
    public function flush();

    /**
     * Clears caches
     *
     * @return void
     */
    public function clear();
}
