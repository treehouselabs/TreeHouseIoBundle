<?php

namespace TreeHouse\IoBundle\Tests\Item;

use TreeHouse\IoBundle\Item\ItemBag;

class ItemFixture
{
    /**
     * @var ItemBag
     */
    protected $actual;

    /**
     * @var ItemBag
     */
    protected $expected;

    /**
     * @param ItemBag $actual
     * @param ItemBag $expected
     */
    public function __construct(ItemBag $actual, ItemBag $expected)
    {
        $this->actual = $actual;
        $this->expected = $expected;
    }

    /**
     * @return ItemBag
     */
    public function getActualItem()
    {
        return $this->actual;
    }

    /**
     * @return ItemBag
     */
    public function getExpectedItem()
    {
        return $this->expected;
    }
}
