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
     * @return int
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

    /**
     * Returns a feed that matches the given origin and reader options
     *
     * @param int $originId
     * @param array $readerOptions
     *
     * @return Feed|null
     */
    public function findOneByOriginAndReaderOptions(
        int $originId,
        array $readerOptions
    ) {
        return $this
            ->createQueryBuilder('f')
            ->where('f.origin = :origin')
            ->andWhere('f.readerOptions = :readerOptions')
            ->setParameters([
                'origin' => $originId,
                'readerOptions' => json_encode($readerOptions),
            ])
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
