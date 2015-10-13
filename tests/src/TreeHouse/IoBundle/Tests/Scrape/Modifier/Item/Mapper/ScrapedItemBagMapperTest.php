<?php

namespace TreeHouse\IoBundle\Tests\Scrape\Modifier\Item\Mapper;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\ParameterBag;
use TreeHouse\IoBundle\Entity\Scraper;
use TreeHouse\IoBundle\Scrape\Modifier\Item\Mapper\ScrapedItemBagMapper;
use TreeHouse\IoBundle\Scrape\ScrapedItemBag;

class ScrapedItemBagMapperTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $callback = function () {};

        $mapper = new ScrapedItemBagMapper($callback, $callback, $callback);

        $this->assertInstanceOf(ScrapedItemBagMapper::class, $mapper);
    }

    /**
     * @dataProvider             getInvalidConstructorArgs
     * @expectedException        \InvalidArgumentException
     * @expectedExceptionMessage must be a callable
     */
    public function testInvalidConstructorArguments($callback)
    {
        new ScrapedItemBagMapper($callback, $callback, $callback);
    }

    public function getInvalidConstructorArgs()
    {
        return [
            [null],
            ['foo'],
            [[]],
            [1234],
        ];
    }

    /**
     * @expectedException        \TreeHouse\Feeder\Exception\TransformationFailedException
     * @expectedExceptionMessage ScrapedItemBag instance
     */
    public function testMapWithoutScrapedItemBag()
    {
        $callback = function () {};

        $mapper = new ScrapedItemBagMapper($callback, $callback, $callback);
        $mapper->setCrawler(new Crawler('<html><body>Test</body></html>'));
        $mapper->map(new ParameterBag());
    }

    /**
     * @expectedException        \LogicException
     * @expectedExceptionMessage setCrawler() should be called before map()
     */
    public function testMapWithoutCrawler()
    {
        $callback = function () {};
        $scraper = new Scraper();
        $url = 'http://example.org';
        $html = '<html><body>Test</body></html>';

        $mapper = new ScrapedItemBagMapper($callback, $callback, $callback);
        $mapper->map(new ScrapedItemBag($scraper, $url, $html));
    }

    public function testMap()
    {
        $originalId = 1234;
        $originalUrl = 'http://example.org';
        $modificationDate = new \DateTime();

        $mapper = new ScrapedItemBagMapper(
            function (Crawler $crawler) use ($originalId) {
                return $originalId;
            },
            function (Crawler $crawler) use ($originalUrl) {
                return $originalUrl;
            },
            function (Crawler $crawler) use ($modificationDate) {
                return $modificationDate;
            }
        );

        $scraper = new Scraper();
        $url = 'http://example.org';
        $html = '<html><body>Test</body></html>';

        $mapper->setCrawler(new Crawler($html));

        /** @var ScrapedItemBag $item */
        $item = $mapper->map(new ScrapedItemBag($scraper, $url, $html));

        $this->assertSame($originalId, $item->getOriginalId());
        $this->assertSame($originalUrl, $item->getOriginalUrl());
        $this->assertSame($modificationDate, $item->getDatetimeModified());
    }
}
