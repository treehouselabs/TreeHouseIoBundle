<?php

namespace TreeHouse\IoBundle\Scrape\Modifier\Item\Mapper;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\ParameterBag;
use TreeHouse\Feeder\Modifier\Item\Mapper\MapperInterface;

class NodeMapper implements MapperInterface, CrawlerAwareInterface
{
    /**
     * @var Crawler
     */
    protected $crawler;

    /**
     * @var array
     */
    protected $mapping = [];

    /**
     * @var string[]
     */
    protected $filters = [];

    /**
     * @var callable[]
     */
    protected $extractors = [];

    /**
     * @param array $mapping
     */
    public function __construct(array $mapping)
    {
        foreach ($mapping as $name => $selector) {
            if (is_string($selector)) {
                $extractor = [$this, 'extractHtml'];
            } elseif (is_array($selector) && sizeof($selector) === 2) {
                list($selector, $extractor) = $selector;
            } else {
                throw new \InvalidArgumentException('A mapping value must be either a string or array<string, callable>');
            }

            $this->addMapping($name, $selector, $extractor);
        }
    }

    /**
     * @return array
     */
    public function getMapping()
    {
        return $this->mapping;
    }

    /**
     * @param string          $name
     * @param string          $selector
     * @param string|callable $extractor
     */
    public function addMapping($name, $selector, $extractor = 'extractHtml')
    {
        if (is_string($extractor) && method_exists($this, $extractor)) {
            $extractor = [$this, $extractor];
        }

        if (!is_callable($extractor)) {
            throw new \InvalidArgumentException(
                sprintf('The extractor of a mapping must be a callable, but got %s', json_encode($extractor))
            );
        }

        $this->mapping[$name]    = $selector;
        $this->extractors[$name] = $extractor;
        $this->filters[$name]    = 'filter';

        if (preg_match('~^//~', $selector) || strpos($selector, 'descendant-or-self::') === 0) {
            $this->filters[$name] = 'filterXpath';
        }
    }

    /**
     * @inheritdoc
     */
    public function setCrawler(Crawler $crawler)
    {
        $this->crawler = $crawler;
    }

    /**
     * @param ParameterBag $item
     *
     * @throws \LogicException
     *
     * @return ParameterBag
     */
    public function map(ParameterBag $item)
    {
        if (null === $this->crawler) {
            throw new \LogicException('setCrawler() should be called before map()');
        }

        foreach ($this->mapping as $name => $selector) {
            $filter    = $this->filters[$name];
            $extractor = $this->extractors[$name];

            /** @var Crawler $node */
            $node = $this->crawler->$filter($selector);

            if ($node->count() === 0) {
                $value = null;
            } else {
                $value = call_user_func($extractor, $name, $node);
            }

            $item->set($name, $value);
        }

        return $item;
    }

    /**
     * @param string  $field
     * @param Crawler $node
     *
     * @return string
     */
    protected function extractHtml($field, Crawler $node)
    {
        return $node->html();
    }

    /**
     * @param string  $field
     * @param Crawler $node
     *
     * @return string
     */
    protected function extractText($field, Crawler $node)
    {
        return $node->text();
    }
}
