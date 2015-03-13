<?php

namespace FM\IoBundle\Scrape;

use FM\Feeder\Event\FailedItemModificationEvent;
use FM\Feeder\Exception\FilterException;
use FM\Feeder\Exception\ModificationException;
use FM\Feeder\Exception\ValidationException;
use FM\Feeder\Modifier\Item\Filter\FilterInterface;
use FM\Feeder\Modifier\Item\Mapper\MapperInterface;
use FM\Feeder\Modifier\Item\ModifierInterface;
use FM\Feeder\Modifier\Item\Transformer\TransformerInterface;
use FM\Feeder\Modifier\Item\Validator\ValidatorInterface;
use FM\IoBundle\Entity\Scraper as ScraperEntity;
use FM\IoBundle\Import\Exception\FailedItemException;
use FM\IoBundle\Import\Handler\HandlerInterface;
use FM\IoBundle\Scrape\Model\ScrapedItemBag;
use FM\IoBundle\Scrape\Modifier\Item\Mapper\NodeMapperInterface;
use Symfony\Component\DomCrawler\Crawler;

class Scraper implements ScraperInterface
{
    /**
     * @var ScraperEntity
     */
    protected $scraper;

    /**
     * @var HandlerInterface
     */
    protected $handler;

    /**
     * @var array<ModifierInterface, bool>
     */
    protected $modifiers = [];

    /**
     * @inheritdoc
     */
    public function addModifier(ModifierInterface $modifier, $continue = null)
    {
        $this->modifiers[] = [$modifier, $continue];
    }

    public function run($crawler)
    {
        while ($item = $this->scrape($crawler)) {
            // import the item
            try {
                $source = $this->handler->handle($this->scraper, $item);
            } catch (FailedItemException $e) {
            }

            // clear entitymanager after batch
            if (($this->processed % $this->batchSize) === 0) {
                $this->flush();
                $this->clear();
            }
        }

        // flush remaining changes
        $this->flush();
        $this->clear();
    }

    /**
     * @inheritdoc
     */
    public function scrape($html, $url)
    {
        $crawler = $this->getCrawler($html);

        $item = new ScrapedItemBag($url);

        foreach ($this->modifiers as list($modifier, $continue)) {
            try {
                if ($modifier instanceof NodeMapperInterface) {
                    $modifier->setCrawler($crawler);
                }

                if ($modifier instanceof FilterInterface) {
                    $modifier->filter($item);
                }

                if ($modifier instanceof MapperInterface) {
                    $item = $modifier->map($item);
                }

                if ($modifier instanceof TransformerInterface) {
                    $modifier->transform($item);
                }

                if ($modifier instanceof ValidatorInterface) {
                    $modifier->validate($item);
                }
            } catch (FilterException $e) {
                // filter exceptions don't get to continue
                throw $e;
            } catch (ValidationException $e) {
                // validation exceptions don't get to continue
                throw $e;
            } catch (ModificationException $e) {
                // notify listeners of this failure, give them the option to stop propagation
                $event = new FailedItemModificationEvent($item, $modifier, $e);
                $event->setContinue($continue);

                $this->eventDispatcher->dispatch(FeedEvents::ITEM_MODIFICATION_FAILED, $event);

                if (!$event->getContinue()) {
                    throw $e;
                }
            }
        }

        return $item;
    }

    /**
     * @inheritdoc
     */
    protected function getCrawler($html)
    {
        return new Crawler($html);
    }

    /**
     * Returns the next item in the feed, or null if no more items are left.
     * Use this when iterating over the feed.
     *
     * @param  Feed             $feed
     * @return FeedItemBag|null
     */
    protected function getNextItem(Feed $feed)
    {
        try {
            return $feed->getNextItem();
        } catch (\Exception $exception) {
            $this->handleException($exception);
        }

        return;
    }

    /**
     * Flushes outstanding changes
     */
    protected function flush()
    {
        $this->handler->flush();
    }

    protected function clear()
    {
        $this->handler->clear();
    }
}
