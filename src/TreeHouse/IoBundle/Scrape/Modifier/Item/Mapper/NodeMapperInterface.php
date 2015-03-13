<?php

namespace FM\IoBundle\Scrape\Modifier\Item\Mapper;

use FM\Feeder\Modifier\Item\Mapper\MapperInterface;
use Symfony\Component\DomCrawler\Crawler;

interface NodeMapperInterface extends MapperInterface
{
    /**
     * @param Crawler $crawler
     */
    public function setCrawler(Crawler $crawler);
}
