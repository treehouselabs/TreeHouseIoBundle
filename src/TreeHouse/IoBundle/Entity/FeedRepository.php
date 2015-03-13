<?php

namespace TreeHouse\IoBundle\Entity;

use Doctrine\ORM\EntityRepository;

class FeedRepository extends EntityRepository
{
    /**
     * Returns the highest frequency value, meaning the actual value: the
     * frequency is stored as number of hours, so a higher number actually means
     * a smaller frequency.
     *
     * @return integer
     */
    public function findHighestFrequencyValue()
    {
        return $this
            ->createQueryBuilder('f')
            ->select('MAX(f.frequency)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
