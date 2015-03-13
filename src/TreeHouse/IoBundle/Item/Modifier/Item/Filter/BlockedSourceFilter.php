<?php

namespace TreeHouse\IoBundle\Item\Modifier\Item\Filter;

use Symfony\Component\HttpFoundation\ParameterBag;
use TreeHouse\Feeder\Exception\FilterException;
use TreeHouse\Feeder\Modifier\Item\Filter\FilterInterface;
use TreeHouse\IoBundle\Import\Model\FeedItemBag;
use TreeHouse\IoBundle\Source\Manager\ImportSourceManager;

/**
 * Filters out items in the feed that correspond to blocked sources.
 */
class BlockedSourceFilter implements FilterInterface
{
    /**
     * @var ImportSourceManager
     */
    protected $sourceManager;

    /**
     * @param ImportSourceManager $sourceManager
     */
    public function __construct(ImportSourceManager $sourceManager)
    {
        $this->sourceManager = $sourceManager;
    }

    /**
     * @inheritdoc
     */
    public function filter(ParameterBag $item)
    {
        /** @var FeedItemBag $item */
        $item->getOriginalId();

        // check if source already exists and is blocked
        if (null === $source = $this->sourceManager->findSource($item->getFeed(), $item->getOriginalId())) {
            return;
        }

        if ($source->isBlocked() === true) {
            throw new FilterException('Source is blocked');
        }
    }
}
