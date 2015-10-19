<?php

namespace TreeHouse\IoBundle\Test\Mock;

use TreeHouse\IoBundle\Entity\Feed;
use TreeHouse\IoBundle\Model\OriginInterface;

class FeedMock extends Feed
{
    public function __construct($id, OriginInterface $origin = null)
    {
        $this->id = $id;
        $this->origin = $origin;
    }
}
