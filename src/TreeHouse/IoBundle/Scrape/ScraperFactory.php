<?php

namespace TreeHouse\IoBundle\Scrape;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use TreeHouse\IoBundle\Entity\Scraper as ScraperEntity;
use TreeHouse\IoBundle\Scrape\Crawler\CrawlerInterface;
use TreeHouse\IoBundle\Scrape\Handler\HandlerInterface;
use TreeHouse\IoBundle\Scrape\Parser\ParserBuilder;
use TreeHouse\IoBundle\Scrape\Parser\ParserInterface;
use TreeHouse\IoBundle\Scrape\Parser\Type\ParserTypeInterface;

class ScraperFactory
{
    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var CrawlerInterface[]
     */
    protected $crawlers = [];

    /**
     * @var ParserTypeInterface[]
     */
    protected $parserTypes = [];

    /**
     * @var HandlerInterface[]
     */
    protected $handlers = [];

    /**
     * @var ParserInterface[]
     */
    protected $parsers = [];

    /**
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(EventDispatcherInterface $dispatcher = null)
    {
        $this->eventDispatcher = $dispatcher;
    }

    /**
     * @return EventDispatcherInterface
     */
    public function getEventDispatcher()
    {
        return $this->eventDispatcher;
    }

    /**
     * @inheritdoc
     */
    public function registerCrawler(CrawlerInterface $crawler, $alias)
    {
        $this->crawlers[$alias] = $crawler;
    }

    /**
     * @param string $alias
     *
     * @return CrawlerInterface
     */
    public function getCrawler($alias)
    {
        if (!array_key_exists($alias, $this->crawlers)) {
            throw new \OutOfBoundsException(
                sprintf(
                    'Crawler "%s" is not registered. You can add it by creating a service which implements %s, ' .
                    'and tag it with tree_house.io.scrape.crawler',
                    $alias,
                    CrawlerInterface::class
                )
            );
        }

        return $this->crawlers[$alias];
    }

    /**
     * @return CrawlerInterface[]
     */
    public function getCrawlers()
    {
        return $this->crawlers;
    }

    /**
     * @inheritdoc
     */
    public function registerParserType(ParserTypeInterface $parser, $alias)
    {
        $this->parserTypes[$alias] = $parser;
    }

    /**
     * @param string $alias
     *
     * @return ParserTypeInterface
     */
    public function getParserType($alias)
    {
        if (!array_key_exists($alias, $this->parserTypes)) {
            throw new \OutOfBoundsException(
                sprintf(
                    'Parser type "%s" is not registered. You can add it by creating a service which implements %s, ' .
                    'and tag it with tree_house.io.scrape.parser_type',
                    $alias,
                    ParserTypeInterface::class
                )
            );
        }

        return $this->parserTypes[$alias];
    }

    /**
     * @return ParserTypeInterface[]
     */
    public function getParserTypes()
    {
        return $this->parserTypes;
    }

    /**
     * @inheritdoc
     */
    public function registerHandler(HandlerInterface $handler, $alias)
    {
        $this->handlers[$alias] = $handler;
    }

    /**
     * @param string $alias
     *
     * @return HandlerInterface
     */
    public function getHandler($alias)
    {
        if (!array_key_exists($alias, $this->handlers)) {
            throw new \OutOfBoundsException(
                sprintf(
                    'Handler "%s" is not registered. You can add it by creating a service which implements %s, ' .
                    'and tag it with tree_house.io.scrape.handler',
                    $alias,
                    HandlerInterface::class
                )
            );
        }

        return $this->handlers[$alias];
    }

    /**
     * @return HandlerInterface[]
     */
    public function getHandlers()
    {
        return $this->handlers;
    }

    /**
     * @param ScraperEntity $scraper
     *
     * @return ScraperInterface
     */
    public function createScraper(ScraperEntity $scraper)
    {
        $parser = $this->getParser($scraper);
        $crawler = $this->getCrawler($scraper->getCrawler());
        $handler = $this->getHandler($scraper->getHandler());

        $builder = new ScraperBuilder($this->eventDispatcher);

        return $builder->build($crawler, $parser, $handler);
    }

    /**
     * Returns a cached copy of the parser for the given scraper.
     *
     * @param ScraperEntity $scraper
     *
     * @return ParserInterface
     */
    protected function getParser(ScraperEntity $scraper)
    {
        if (!isset($this->parsers[$scraper->getId()])) {
            $this->parsers[$scraper->getId()] = $this->createParser($scraper);
        }

        return $this->parsers[$scraper->getId()];
    }

    /**
     * @param ScraperEntity $scraper
     *
     * @return ParserInterface
     */
    protected function createParser(ScraperEntity $scraper)
    {
        $options = array_merge(
            ['scraper' => $scraper],
            $scraper->getParserOptions()
        );

        $parserType = $this->getParserType($scraper->getParser());
        $builder = new ParserBuilder($this->eventDispatcher);

        return $builder->build($parserType, $options);
    }
}
