<?php

namespace TreeHouse\IoIntegrationBundle\Source;

use DateTime;
use Doctrine\Common\Persistence\ManagerRegistry;
use TreeHouse\IoBundle\Entity\Feed;
use TreeHouse\IoBundle\Entity\Scraper;
use TreeHouse\IoBundle\Model\SourceInterface;
use TreeHouse\IoBundle\Source\SourceManagerInterface;
use TreeHouse\IoIntegrationBundle\Entity\Source;

class SourceManager implements SourceManagerInterface
{
    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    /**
     * @param ManagerRegistry $doctrine
     */
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * @inheritdoc
     */
    public function getRepository()
    {
        return $this->doctrine->getRepository('TreeHouseIoIntegrationBundle:Source');
    }

    /**
     * @inheritdoc
     */
    public function findSourceByFeed(Feed $feed, $originalId)
    {
        // look for mapping
        $params = ['feed' => $feed->getId(), 'originalId' => $originalId];

        return $this->getRepository()->findOneBy($params);
    }

    /**
     * @inheritdoc
     */
    public function findSourceByScraper(Scraper $scraper, $originalId)
    {
        // look for mapping
        $params = ['scraper' => $scraper->getId(), 'originalId' => $originalId];

        return $this->getRepository()->findOneBy($params);
    }

    /**
     * @inheritdoc
     */
    public function findSourceByFeedOrCreate(Feed $feed, $originalId, $originalUrl = null)
    {
        if (null !== $source = $this->findSourceByFeed($feed, $originalId)) {
            return $source;
        }

        $source = new Source();
        $source->setFeed($feed);
        $source->setOriginalId($originalId);
        $source->setOriginalUrl($originalUrl);
        $source->setBlocked(false);
        $source->setDatetimeLastVisited(new DateTime());
        $source->setDatetimeModified(new DateTime());
        $source->setDatetimeImported(new DateTime());

        return $source;
    }

    /**
     * @inheritdoc
     */
    public function findSourceByScraperOrCreate(Scraper $scraper, $originalId, $originalUrl)
    {
        if (null !== $source = $this->findSourceByScraper($scraper, $originalUrl)) {
            return $source;
        }

        $source = new Source();
        $source->setScraper($scraper);
        $source->setOriginalId($originalId);
        $source->setOriginalUrl($originalUrl);
        $source->setBlocked(false);
        $source->setDatetimeLastVisited(new DateTime());
        $source->setDatetimeModified(new DateTime());
        $source->setDatetimeImported(new DateTime());

        return $source;
    }

    /**
     * @inheritdoc
     */
    public function findById($sourceId)
    {
        return $this->getRepository()->find($sourceId);
    }

    /**
     * @inheritdoc
     */
    public function persist(SourceInterface $source)
    {
        $this->doctrine->getManager()->persist($source);
    }

    /**
     * @inheritdoc
     */
    public function remove(SourceInterface $source)
    {
        $this->doctrine->getManager()->remove($source);
    }

    /**
     * @inheritdoc
     */
    public function detach(SourceInterface $source)
    {
        $this->doctrine->getManager()->detach($source);
    }

    /**
     * @inheritdoc
     */
    public function flush(SourceInterface $source = null)
    {
        $this->doctrine->getManager()->flush($source);
    }

    /**
     * @inheritdoc
     */
    public function clear()
    {
        $this->doctrine->getManager()->clear('TreeHouseIoIntegrationBundle:Source');
    }
}
