<?php

namespace TreeHouse\IoBundle\Event;

use TreeHouse\IoBundle\Entity\Feed;

class FeedCleanupHaltEvent extends CleanupHaltEvent
{
    /**
     * @var Feed
     */
    protected $feed;

    /**
     * @param Feed $feed
     * @param int  $count
     * @param int  $total
     * @param int  $max
     */
    public function __construct(Feed $feed, $count, $total, $max)
    {
        parent::__construct($count, $total, $max);

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
