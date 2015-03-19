<?php

namespace TreeHouse\IoBundle\Scrape\Modifier\Item\Mapper;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\ParameterBag;
use TreeHouse\Feeder\Exception\TransformationFailedException;
use TreeHouse\Feeder\Modifier\Item\Mapper\MapperInterface;
use TreeHouse\IoBundle\Scrape\ScrapedItemBag;

class ScrapedItemBagMapper implements MapperInterface, CrawlerAwareInterface
{
    /**
     * @var Crawler
     */
    protected $crawler;

    /**
     * @var callable
     */
    protected $originalIdCallback;

    /**
     * @var \Closure
     */
    protected $originalUrlCallback;

    /**
     * @var callable
     */
    protected $modificationDateCallback;

    /**
     * @param callable $originalIdCallback
     * @param callable $originalUrlCallback
     * @param callable $modificationDateCallback
     */
    public function __construct($originalIdCallback, $originalUrlCallback, $modificationDateCallback)
    {
        if (!is_callable($originalIdCallback)) {
            throw new \InvalidArgumentException('$originalIdCallback must be a callable');
        }

        if (!is_callable($originalUrlCallback)) {
            throw new \InvalidArgumentException('$originalUrlCallback must be a callable');
        }

        if (!is_callable($modificationDateCallback)) {
            throw new \InvalidArgumentException('$modificationDateCallback must be a callable');
        }

        $this->originalIdCallback       = $originalIdCallback;
        $this->originalUrlCallback      = $originalUrlCallback;
        $this->modificationDateCallback = $modificationDateCallback;
    }

    /**
     * @inheritdoc
     */
    public function setCrawler(Crawler $crawler)
    {
        $this->crawler = $crawler;
    }

    /**
     * @inheritdoc
     */
    public function map(ParameterBag $item)
    {
        if (null === $this->crawler) {
            throw new \LogicException('setCrawler() should be called before map()');
        }

        if (!$item instanceof ScrapedItemBag) {
            throw new TransformationFailedException(sprintf('Expected a %s instance', ScrapedItemBag::class));
        }

        if ($url = call_user_func($this->originalUrlCallback, $this->crawler)) {
            $item->setOriginalUrl($url);
        }

        if ($id = call_user_func($this->originalIdCallback, $this->crawler)) {
            $item->setOriginalId($id);
        }

        if ($date = call_user_func($this->modificationDateCallback, $this->crawler)) {
            $item->setDatetimeModified($date);
        }

        return $item;
    }
}
