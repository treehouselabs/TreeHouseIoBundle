<?php

namespace TreeHouse\IoBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use TreeHouse\IoBundle\Entity\Feed;

class FeedEvent extends Event
{
    /**
     * @var Feed
     */
    protected $feed;

    /**
     * @param Feed $feed
     */
    public function __construct(Feed $feed)
    {
        $this->feed = $feed;
    }

    /**
     * @return Feed
     */
    public function getFeed()
    {
        return $this->feed;
    }
}
