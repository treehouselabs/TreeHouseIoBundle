<?php

namespace TreeHouse\IoBundle\Item\Modifier\Item\Filter;

use DateTime;
use Symfony\Component\HttpFoundation\ParameterBag;
use TreeHouse\Feeder\Exception\FilterException;
use TreeHouse\Feeder\Modifier\Item\Filter\FilterInterface;
use TreeHouse\IoBundle\Import\Feed\FeedItemBag;
use TreeHouse\IoBundle\Item\ItemBag;
use TreeHouse\IoBundle\Model\SourceInterface;
use TreeHouse\IoBundle\Scrape\ScrapedItemBag;
use TreeHouse\IoBundle\Source\Manager\CachedSourceManager;

/**
 * Filters out items in the feed that have a modification date
 * before its corresponding source's modification date.
 */
class ModifiedItemFilter implements FilterInterface
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
        /** @var FeedItemBag $item */
        // if source does not exist yet, by all means process it
        if (null === $source = $this->findSource($item)) {
            return;
        }

        // first try modification date
        if (null !== $mutationDate = $item->getDatetimeModified()) {
            if ($source->getDatetimeImported() > $mutationDate) {
                throw new FilterException('Item is not modified');
            }
        }
        $source->setDatetimeImported(new DateTime());

        // item is modified or we don't have enough information to determine that, either way continue.
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
