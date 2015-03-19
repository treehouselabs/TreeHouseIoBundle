<?php

namespace TreeHouse\IoBundle\Import\Feed;

use TreeHouse\IoBundle\Entity\Feed;
use TreeHouse\IoBundle\Item\ItemBag;

class FeedItemBag extends ItemBag
{
    /**
     * @var Feed
     */
    protected $feed;

    /**
     * @param Feed   $feed
     * @param string $originalId
     * @param array  $parameters
     */
    public function __construct(Feed $feed, $originalId, array $parameters = [])
    {
        parent::__construct($parameters);

        $this->feed       = $feed;
        $this->originalId = $originalId;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return sprintf('%s:%s', $this->feed->getOrigin()->getName(), $this->originalId);
    }

    /**
     * @return Feed
     */
    public function getFeed()
    {
        return $this->feed;
    }
}
