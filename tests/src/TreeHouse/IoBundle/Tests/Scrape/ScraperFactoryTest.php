<?php

namespace TreeHouse\IoBundle\Tests\Scrape;

use Symfony\Component\EventDispatcher\EventDispatcher;
use TreeHouse\IoBundle\Scrape\Crawler\CrawlerInterface;
use TreeHouse\IoBundle\Scrape\Handler\HandlerInterface;
use TreeHouse\IoBundle\Scrape\Parser\Type\ParserTypeInterface;
use TreeHouse\IoBundle\Scrape\ScraperFactory;

class ScraperFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ScraperFactory
     */
    protected $factory;

    protected function setUp()
    {
        $this->factory = new ScraperFactory();
    }

    public function testConstructor()
    {
        $this->assertInstanceOf(ScraperFactory::class, $this->factory);
    }

    public function testConstructWithDispatcher()
    {
        $dispatcher = new EventDispatcher();
        $factory = new ScraperFactory($dispatcher);

        $this->assertSame($dispatcher, $factory->getEventDispatcher());
    }

    public function testRegisterCrawler()
    {
        $crawler = $this->getMockForAbstractClass(CrawlerInterface::class);
        $alias = 'foo';

        $this->factory->registerCrawler($crawler, $alias);
        $this->assertSame($crawler, $this->factory->getCrawler($alias));
    }

    /**
     * @expectedException        \OutOfBoundsException
     * @expectedExceptionMessage Crawler "foo" is not registered
     */
    public function testMissingCrawler()
    {
        $this->factory->getCrawler('foo');
    }

    public function testRegisterParserType()
    {
        $type = $this->getMockForAbstractClass(ParserTypeInterface::class);
        $alias = 'foo';

        $this->factory->registerParserType($type, $alias);
        $this->assertSame($type, $this->factory->getParserType($alias));
    }

    /**
     * @expectedException        \OutOfBoundsException
     * @expectedExceptionMessage Parser type "foo" is not registered
     */
    public function testMissingParserType()
    {
        $this->factory->getParserType('foo');
    }

    public function testRegisterHandler()
    {
        $handler = $this->getMockForAbstractClass(HandlerInterface::class);
        $alias = 'foo';

        $this->factory->registerHandler($handler, $alias);
        $this->assertSame($handler, $this->factory->getHandler($alias));
    }

    /**
     * @expectedException        \OutOfBoundsException
     * @expectedExceptionMessage Handler "foo" is not registered
     */
    public function testMissingHandler()
    {
        $this->factory->getHandler('foo');
    }
}
