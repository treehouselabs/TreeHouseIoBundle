<?php

namespace TreeHouse\IoBundle\Item\Modifier\Item\Mapper;

use Symfony\Component\HttpFoundation\ParameterBag;
use TreeHouse\Feeder\Modifier\Item\Mapper\MapperInterface;
use TreeHouse\IoBundle\Entity\Feed;
use TreeHouse\IoBundle\Import\Feed\FeedItemBag;

class FeedItemBagMapper implements MapperInterface
{
    /**
     * @var Feed
     */
    protected $feed;

    /**
     * @var \Closure
     */
    protected $originalIdCallback;

    /**
     * @var \Closure
     */
    protected $originalUrlCallback;

    /**
     * @var \Closure
     */
    protected $modificationDateCallback;

    /**
     * @param Feed     $feed
     * @param callable $originalIdCallback
     * @param callable $originalUrlCallback
     * @param callable $modificationDateCallback
     */
    public function __construct(Feed $feed, $originalIdCallback, $originalUrlCallback, $modificationDateCallback
    ) {
        $this->feed = $feed;
        $this->originalIdCallback = $originalIdCallback;
        $this->originalUrlCallback = $originalUrlCallback;
        $this->modificationDateCallback = $modificationDateCallback;
    }

    /**
     * {@inheritdoc}
     */
    public function map(ParameterBag $item)
    {
        $bag = new FeedItemBag($this->feed, call_user_func($this->originalIdCallback, $item), $item->all());
        $bag->setOriginalUrl(call_user_func($this->originalUrlCallback, $item));
        $bag->setDatetimeModified(call_user_func($this->modificationDateCallback, $item));

        return $bag;
    }
}
