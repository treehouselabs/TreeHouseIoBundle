<?php

namespace TreeHouse\IoBundle\Scrape\Parser;

use Symfony\Component\DomCrawler\Crawler as DomCrawler;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use TreeHouse\Feeder\Event\FailedItemModificationEvent;
use TreeHouse\Feeder\Exception\FilterException;
use TreeHouse\Feeder\Exception\ModificationException;
use TreeHouse\Feeder\Exception\ValidationException;
use TreeHouse\Feeder\FeedEvents;
use TreeHouse\Feeder\Modifier\Item\Filter\FilterInterface;
use TreeHouse\Feeder\Modifier\Item\Mapper\MapperInterface;
use TreeHouse\Feeder\Modifier\Item\ModifierInterface;
use TreeHouse\Feeder\Modifier\Item\Transformer\TransformerInterface;
use TreeHouse\Feeder\Modifier\Item\Validator\ValidatorInterface;
use TreeHouse\IoBundle\Scrape\Modifier\Item\Mapper\CrawlerAwareInterface;
use TreeHouse\IoBundle\Scrape\ScrapedItemBag;

class DefaultParser implements ParserInterface
{
    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var ModifierInterface[]
     */
    protected $modifiers = [];

    /**
     * @var array
     */
    protected $continues = [];

    /**
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(EventDispatcherInterface $eventDispatcher = null)
    {
        $this->eventDispatcher = $eventDispatcher ?: new EventDispatcher();
    }

    /**
     * @inheritdoc
     */
    public function getEventDispatcher()
    {
        return $this->eventDispatcher;
    }

    /**
     * @inheritdoc
     */
    public function getModifiers()
    {
        return $this->modifiers;
    }

    /**
     * @inheritdoc
     */
    public function addModifier(ModifierInterface $modifier, $position = null, $continueOnException = false)
    {
        if (null === $position) {
            $position = sizeof($this->modifiers) ? (max(array_keys($this->modifiers)) + 1) : 0;
        }

        if (!is_numeric($position)) {
            throw new \InvalidArgumentException('Position must be a number');
        }

        if (array_key_exists($position, $this->modifiers)) {
            throw new \InvalidArgumentException(sprintf('There already is a modifier at position %d', $position));
        }

        $this->modifiers[$position] = $modifier;
        $this->continues[$position] = $continueOnException;

        ksort($this->modifiers);
    }

    /**
     * @param ModifierInterface $modifier
     */
    public function removeModifier(ModifierInterface $modifier)
    {
        foreach ($this->modifiers as $position => $_modifier) {
            if ($_modifier === $modifier) {
                unset($this->modifiers[$position]);

                break;
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function removeModifierAt($position)
    {
        if (!array_key_exists($position, $this->modifiers)) {
            throw new \OutOfBoundsException(sprintf('There is no modifier at position %d', $position));
        }

        unset($this->modifiers[$position]);
    }

    /**
     * @inheritdoc
     */
    public function hasModifierAt($position)
    {
        return array_key_exists($position, $this->modifiers);
    }

    /**
     * @inheritdoc
     */
    public function parse(ScrapedItemBag $item)
    {
        $crawler = $this->getDomCrawler($item->getOriginalData(), $item->getOriginalUrl());

        foreach ($this->modifiers as $position => $modifier) {
            // set crawler if needed
            if ($modifier instanceof CrawlerAwareInterface) {
                $modifier->setCrawler($crawler);
            }

            try {
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
                $event->setContinue($this->continues[$position]);

                $this->eventDispatcher->dispatch(FeedEvents::ITEM_MODIFICATION_FAILED, $event);

                if (!$event->getContinue()) {
                    throw $e;
                }
            }
        }
    }

    /**
     * @param string $html
     * @param string $url
     *
     * @return DomCrawler
     */
    protected function getDomCrawler($html, $url)
    {
        return new DomCrawler($html, $url);
    }
}
