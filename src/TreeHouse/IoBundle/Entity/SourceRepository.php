<?php

namespace TreeHouse\IoBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use TreeHouse\IoBundle\Model\SourceInterface;

class SourceRepository extends EntityRepository
{
    /**
     * Queries number of sources for a feed.
     *
     * @param Feed $feed
     *
     * @return QueryBuilder
     */
    public function queryByFeed(Feed $feed)
    {
        return $this->createQueryBuilder('s')
            ->where('s.feed = :feed')
            ->setParameter('feed', $feed)
        ;
    }

    /**
     * Counts number of sources for a feed.
     *
     * @param Feed $feed
     *
     * @return int
     */
    public function countByFeed(Feed $feed)
    {
        $builder = $this->queryByFeed($feed);
        $builder->select('COUNT(s)');

        return $builder->getQuery()->getSingleScalarResult();
    }

    /**
     * Queries number of sources for a scraper.
     *
     * @param Scraper $scraper
     *
     * @return QueryBuilder
     */
    public function queryByScraper(Scraper $scraper)
    {
        return $this->createQueryBuilder('s')
            ->where('s.scraper = :scraper')
            ->setParameter('scraper', $scraper)
        ;
    }

    /**
     * Counts number of sources for a scraper.
     *
     * @param Scraper $scraper
     *
     * @return int
     */
    public function countByScraper(Scraper $scraper)
    {
        $builder = $this->queryByScraper($scraper);
        $builder->select('COUNT(s)');

        return $builder->getQuery()->getSingleScalarResult();
    }

    /**
     * Queries number of sources for a feed, not visited since a given date.
     *
     * @param Feed      $feed
     * @param \DateTime $dateLastVisited
     *
     * @return QueryBuilder
     */
    public function queryByFeedAndUnvisitedSince(Feed $feed, \DateTime $dateLastVisited)
    {
        return $this->queryByFeed($feed)
            ->andWhere('s.datetimeLastVisited < :datetimeLastVisited')
            ->orderBy('s.datetimeLastVisited', 'ASC')
            ->setParameter('datetimeLastVisited', $dateLastVisited)
        ;
    }

    /**
     * Finds number of sources for a feed, not visited since a given date.
     *
     * @param Feed      $feed
     * @param \DateTime $dateLastVisited
     *
     * @return SourceInterface[]
     */
    public function findByFeedAndUnvisitedSince(Feed $feed, \DateTime $dateLastVisited)
    {
        return $this->queryByFeedAndUnvisitedSince($feed, $dateLastVisited)->getQuery()->getResult();
    }

    /**
     * Counts number of sources for a feed, not visited since a given date.
     *
     * @param Feed      $feed
     * @param \DateTime $dateLastVisited
     *
     * @return int
     */
    public function countByFeedAndUnvisitedSince(Feed $feed, \DateTime $dateLastVisited)
    {
        $builder = $this->queryByFeedAndUnvisitedSince($feed, $dateLastVisited);
        $builder->select('COUNT(s)');

        return $builder->getQuery()->getSingleScalarResult();
    }

    /**
     * Queries number of sources for a scraper, not visited since a given date.
     *
     * @param Scraper   $scraper
     * @param \DateTime $dateLastVisited
     *
     * @return QueryBuilder
     */
    public function queryByScraperAndUnvisitedSince(Scraper $scraper, \DateTime $dateLastVisited)
    {
        return $this->queryByScraper($scraper)
            ->andWhere('s.datetimeLastVisited < :datetimeLastVisited')
            ->orderBy('s.datetimeLastVisited', 'ASC')
            ->setParameter('datetimeLastVisited', $dateLastVisited)
        ;
    }

    /**
     * Finds number of sources for a scraper, not visited since a given date.
     *
     * @param Scraper   $scraper
     * @param \DateTime $dateLastVisited
     *
     * @return SourceInterface[]
     */
    public function findByScraperAndUnvisitedSince(Scraper $scraper, \DateTime $dateLastVisited)
    {
        return $this->queryByScraperAndUnvisitedSince($scraper, $dateLastVisited)->getQuery()->getResult();
    }

    /**
     * Counts number of sources for a scraper, not visited since a given date.
     *
     * @param Scraper   $scraper
     * @param \DateTime $dateLastVisited
     *
     * @return int
     */
    public function countByScraperAndUnvisitedSince(Scraper $scraper, \DateTime $dateLastVisited)
    {
        $builder = $this->queryByScraperAndUnvisitedSince($scraper, $dateLastVisited);
        $builder->select('COUNT(s)');

        return $builder->getQuery()->getSingleScalarResult();
    }

    /**
     * Returns orphaned sources, meaning those without a feed or scraper.
     *
     * @return QueryBuilder
     */
    public function queryOrphaned()
    {
        return $this
            ->createQueryBuilder('s')
            ->where('s.feed IS NULL')
            ->andWhere('s.scraper IS NULL')
        ;
    }
}
