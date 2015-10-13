<?php

namespace TreeHouse\IoBundle\Scrape;

use TreeHouse\IoBundle\Scrape\Crawler\CrawlerInterface;
use TreeHouse\IoBundle\Scrape\Handler\HandlerInterface;
use TreeHouse\IoBundle\Scrape\Parser\ParserInterface;

interface ScraperBuilderInterface
{
    /**
     * Builds a scraper.
     *
     * @param CrawlerInterface $crawler
     * @param ParserInterface  $parser
     * @param HandlerInterface $handler
     *
     * @return ScraperInterface
     */
    public function build(CrawlerInterface $crawler, ParserInterface $parser, HandlerInterface $handler);
}
