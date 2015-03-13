<?php

namespace TreeHouse\IoBundle\Export\FeedType;

use Doctrine\ORM\QueryBuilder;

/**
 * Abstract class to create a new Feed Type with specific settings
 */
abstract class AbstractFeedType
{
    /**
     * @return string
     */
    abstract public function getTemplate();

    /**
     * @return string
     */
    abstract public function getName();

    /**
     * @return string
     */
    abstract public function getRootNode();

    /**
     * @return string
     */
    public function getNamespaces()
    {
        return '';
    }

    /**
     * @return int time-to-live in minutes
     */
    public function getTtl()
    {
        return 180;
    }

    /**
     * @param  object $entity
     * @return bool
     */
    abstract public function supports($entity);

    /**
     * @param string $alias
     *
     * @return QueryBuilder
     */
    abstract public function getQueryBuilder($alias);
}
