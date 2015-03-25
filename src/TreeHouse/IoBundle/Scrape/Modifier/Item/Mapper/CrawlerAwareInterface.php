<?php

namespace TreeHouse\IoBundle\Scrape\Modifier\Item\Mapper;

use Symfony\Component\DomCrawler\Crawler;

interface CrawlerAwareInterface
{
    /**
     * @param Crawler $crawler
     */
    public function setCrawler(Crawler $crawler);
}
