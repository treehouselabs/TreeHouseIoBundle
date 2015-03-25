<?php

namespace TreeHouse\IoBundle\Export\FeedType;

/**
 * Abstract class to create a new Feed Type with specific settings
 */
abstract class AbstractFeedType implements FeedTypeInterface
{
    /**
     * @inheritdoc
     */
    public function getNamespaces()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getTtl()
    {
        return 180;
    }
}
