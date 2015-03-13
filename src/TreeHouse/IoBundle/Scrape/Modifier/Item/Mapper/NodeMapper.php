<?php

namespace FM\IoBundle\Scrape\Modifier\Item\Mapper;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\ParameterBag;

class NodeMapper implements NodeMapperInterface
{
    /**
     * @var Crawler
     */
    protected $crawler;

    /**
     * @var array
     */
    protected $mapping;

    /**
     * @param array $mapping
     */
    public function __construct(array $mapping)
    {
        $this->mapping = $mapping;
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

        foreach ($this->mapping as $field => $selector) {
            if (preg_match('/^\/\//', $selector)) {
                $node = $this->crawler->filterXPath($selector);
            } else {
                $node = $this->crawler->filter($selector);
            }

            $value = ($node->count() > 0) ? $this->getNodeHtmlValue($field, $node) : null;
            $item->set($field, $value);
        }

        return $item;
    }

    /**
     * @param string  $field
     * @param Crawler $node
     *
     * @return string
     */
    protected function getNodeHtmlValue($field, Crawler $node)
    {
        return $node->html();
    }
}
