<?php

namespace TreeHouse\IoBundle\Tests\Import\FeedType;

use TreeHouse\IoBundle\Import\Feed\FeedItemBag;

class ItemFixture
{
    /**
     * @var FeedItemBag
     */
    protected $item;

    /**
     * @var FeedItemBag
     */
    protected $expected;

    /**
     * @param FeedItemBag $item
     * @param FeedItemBag $expected
     */
    public function __construct(FeedItemBag $item, FeedItemBag $expected)
    {
        $this->item = $item;
        $this->expected = $expected;
    }

    /**
     * @return FeedItemBag
     */
    public function getItem()
    {
        return $this->item;
    }

    /**
     * @return FeedItemBag
     */
    public function getExpectedItem()
    {
        return $this->expected;
    }
}
