<?php

namespace TreeHouse\IoBundle\Export\FeedType;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Templating\TemplateReferenceInterface;

interface FeedTypeInterface
{
    /**
     * Returns the name of this feed type.
     *
     * @return string
     */
    public function getName();

    /**
     * Returns either a path to the template, that the templating engine
     * understands (logical, relative or absolute), or a template reference.
     *
     * The cache works best when the template can be resolved with `file_exists()`,
     * since that will refresh the cache based on the template contents.
     *
     * @return string|TemplateReferenceInterface
     */
    public function getTemplate();

    /**
     * Returns the root node name to use in the export.
     *
     * @return string
     */
    public function getRootNode();

    /**
     * Returns the node name to use for items in the export.
     *
     * @return string
     */
    public function getItemNode();

    /**
     * Returns an array of XML namespaces to use in the export.
     *
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
