<?php

namespace TreeHouse\IoBundle\Event;

use TreeHouse\IoBundle\Entity\Feed;

class FeedCleanupEvent extends CleanupEvent
{
    /**
     * @var Feed
     */
    protected $feed;

    /**
     * @param Feed $feed
     * @param int  $numCleaned
     */
    public function __construct(Feed $feed, $numCleaned)
    {
        parent::__construct($numCleaned);

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
