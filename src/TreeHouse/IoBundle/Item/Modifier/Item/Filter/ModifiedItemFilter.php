<?php

namespace TreeHouse\IoBundle\Item\Modifier\Item\Filter;

use Symfony\Component\HttpFoundation\ParameterBag;
use TreeHouse\Feeder\Exception\FilterException;
use TreeHouse\Feeder\Modifier\Item\Filter\FilterInterface;
use TreeHouse\IoBundle\Import\Feed\FeedItemBag;
use TreeHouse\IoBundle\Source\Manager\ImportSourceManager;

/**
 * Filters out items in the feed that have a modification date
 * before its corresponding source's modification date.
 */
class ModifiedItemFilter implements FilterInterface
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
     * {@inheritdoc}
     */
    public function filter(ParameterBag $item)
    {
        /** @var FeedItemBag $item */
        // if source does not exist yet, by all means process it
        if (null === $source = $this->sourceManager->findSource($item->getFeed(), $item->getOriginalId())) {
            return;
        }

        // first try modification date
        if (null !== $mutationDate = $item->getDatetimeModified()) {
            if ($source->getDatetimeModified() > $mutationDate) {
                throw new FilterException('Item is not modified');
            }
        }

        // item is modified or we don't have enough information to determine that, either way continue.
    }
}
