<?php

namespace TreeHouse\IoIntegrationBundle\Export\Feed\Type;

use Doctrine\Common\Persistence\ManagerRegistry;
use TreeHouse\IoBundle\Export\FeedType\AbstractFeedType;
use TreeHouse\IoIntegrationBundle\Entity\Episode;

class PodcastFeedType extends AbstractFeedType
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
    public function getName()
    {
        return 'podcast';
    }

    /**
     * @inheritdoc
     */
    public function getTemplate()
    {
        return 'TreeHouseIoIntegrationBundle:Feed:podcast.xml.twig';
    }

    /**
     * @inheritdoc
     */
    public function getRootNode()
    {
        return 'episodes';
    }

    /**
     * @inheritdoc
     */
    public function getItemNode()
    {
        return 'episode';
    }

    /**
     * @inheritdoc
     */
    public function supports($entity)
    {
        return $entity instanceof Episode;
    }

    /**
     * @inheritdoc
     */
    public function getQueryBuilder($alias)
    {
        return $this->doctrine
            ->getRepository('TreeHouseIoIntegrationBundle:Episode')
            ->createQueryBuilder($alias)
        ;
    }
}
