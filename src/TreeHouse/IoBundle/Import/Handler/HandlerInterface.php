<?php

namespace TreeHouse\IoBundle\Import\Handler;

use TreeHouse\IoBundle\Import\Feed\FeedItemBag;
use TreeHouse\IoBundle\Model\SourceInterface;

interface HandlerInterface
{
    /**
     * @param FeedItemBag $item
     *
     * @return SourceInterface
     */
    public function handle(FeedItemBag $item);

    /**
     * Flushes outstanding changes
     *
     * @return void
     */
    public function flush();

    /**
     * Clears caches
     *
     * @return void
     */
    public function clear();
}
