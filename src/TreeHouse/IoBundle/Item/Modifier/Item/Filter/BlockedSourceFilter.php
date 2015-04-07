<?php

namespace TreeHouse\IoBundle\Item\Modifier\Item\Filter;

use Symfony\Component\HttpFoundation\ParameterBag;
use TreeHouse\Feeder\Exception\FilterException;
use TreeHouse\Feeder\Modifier\Item\Filter\FilterInterface;
use TreeHouse\IoBundle\Import\Feed\FeedItemBag;
use TreeHouse\IoBundle\Item\ItemBag;
use TreeHouse\IoBundle\Model\SourceInterface;
use TreeHouse\IoBundle\Scrape\ScrapedItemBag;
use TreeHouse\IoBundle\Source\Manager\CachedSourceManager;

/**
 * Filters out items in the feed that correspond to blocked sources.
 */
class BlockedSourceFilter implements FilterInterface
{
    /**
     * @var CachedSourceManager
     */
    protected $sourceManager;

    /**
     * @param CachedSourceManager $sourceManager
     */
    public function __construct(CachedSourceManager $sourceManager)
    {
        $this->sourceManager = $sourceManager;
    }

    /**
     * @inheritdoc
     *
     * @param ItemBag $item
     */
    public function filter(ParameterBag $item)
    {
        $item->getOriginalId();

        // check if source already exists and is blocked
        if (null === $source = $this->findSource($item)) {
            return;
        }

        if ($source->isBlocked() === true) {
            throw new FilterException('Source is blocked');
        }
    }

    /**
     * @param ItemBag $item
     *
     * @return null|SourceInterface
     */
    protected function findSource(ItemBag $item)
    {
        if ($item instanceof FeedItemBag) {
            return $this->sourceManager->findSourceByFeed($item->getFeed(), $item->getOriginalId());
        }

        if ($item instanceof ScrapedItemBag) {
            return $this->sourceManager->findSourceByScraper($item->getScraper(), $item->getOriginalId());
        }

        return null;
    }
}
