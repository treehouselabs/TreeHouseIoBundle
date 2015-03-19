<?php

namespace TreeHouse\IoBundle\Export\FeedType;

use Doctrine\ORM\QueryBuilder;

interface FeedTypeInterface
{
    /**
     * @return string
     */
    public function getName();

    /**
     * @return string
     */
    public function getTemplate();

    /**
     * @return string
     */
    public function getRootNode();

    /**
     * @return string
     */
    public function getItemNode();

    /**
     * @return array<string, string>
     */
    public function getNamespaces();

    /**
     * @return integer time-to-live in minutes
     */
    public function getTtl();

    /**
     * @param object $entity
     *
     * @return boolean
     */
    public function supports($entity);

    /**
     * @param string $alias
     *
     * @return QueryBuilder
     */
    public function getQueryBuilder($alias);
}
