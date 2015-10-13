<?php

namespace TreeHouse\IoBundle\Tests\Scrape;

use Symfony\Component\EventDispatcher\EventDispatcher;
use TreeHouse\IoBundle\Scrape\Crawler\CrawlerInterface;
use TreeHouse\IoBundle\Scrape\Handler\HandlerInterface;
use TreeHouse\IoBundle\Scrape\Parser\ParserInterface;
use TreeHouse\IoBundle\Scrape\ScraperBuilder;
use TreeHouse\IoBundle\Scrape\ScraperInterface;

class ScraperBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ScraperBuilder
     */
    protected $builder;

    protected function setUp()
    {
        $this->builder = new ScraperBuilder();
    }

    public function testConstructor()
    {
        $this->assertInstanceOf(ScraperBuilder::class, $this->builder);
    }

    public function testBuild()
    {
        $crawler = $this->getMockForAbstractClass(CrawlerInterface::class);
        $parser = $this->getMockForAbstractClass(ParserInterface::class);
        $handler = $this->getMockForAbstractClass(HandlerInterface::class);

        $builder = new ScraperBuilder();
        $scraper = $builder->build($crawler, $parser, $handler);

        $this->assertInstanceOf(ScraperInterface::class, $scraper);
    }

    public function testBuildWithDispatcher()
    {
        $crawler = $this->getMockForAbstractClass(CrawlerInterface::class);
        $parser = $this->getMockForAbstractClass(ParserInterface::class);
        $handler = $this->getMockForAbstractClass(HandlerInterface::class);

        $dispatcher = new EventDispatcher();
        $builder = new ScraperBuilder($dispatcher);
        $scraper = $builder->build($crawler, $parser, $handler);

        $this->assertSame($dispatcher, $scraper->getEventDispatcher());
    }
}
