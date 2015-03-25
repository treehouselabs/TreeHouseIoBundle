<?php

namespace TreeHouse\IoBundle\Scrape;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use TreeHouse\IoBundle\Scrape\Crawler\CrawlerInterface;
use TreeHouse\IoBundle\Scrape\Handler\HandlerInterface;
use TreeHouse\IoBundle\Scrape\Parser\ParserInterface;

class ScraperBuilder implements ScraperBuilderInterface
{
    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(EventDispatcherInterface $dispatcher = null)
    {
        $this->eventDispatcher = $dispatcher;
    }

    /**
     * @inheritdoc
     */
    public function build(CrawlerInterface $crawler, ParserInterface $parser, HandlerInterface $handler)
    {
        return new Scraper($crawler, $parser, $handler, $this->eventDispatcher);
    }
}
