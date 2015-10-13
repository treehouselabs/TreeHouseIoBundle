<?php

namespace TreeHouse\IoBundle\Tests\Scrape;

use GuzzleHttp\Psr7\Response;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use TreeHouse\Feeder\Exception\FilterException;
use TreeHouse\Feeder\Exception\ModificationException;
use TreeHouse\Feeder\Exception\ValidationException;
use TreeHouse\IoBundle\Entity\Scraper as ScraperEntity;
use TreeHouse\IoBundle\Scrape\Crawler\CrawlerInterface;
use TreeHouse\IoBundle\Scrape\Event\FailedItemEvent;
use TreeHouse\IoBundle\Scrape\Event\RateLimitEvent;
use TreeHouse\IoBundle\Scrape\Event\ScrapeResponseEvent;
use TreeHouse\IoBundle\Scrape\Event\ScrapeUrlEvent;
use TreeHouse\IoBundle\Scrape\Event\SkippedItemEvent;
use TreeHouse\IoBundle\Scrape\Event\SuccessItemEvent;
use TreeHouse\IoBundle\Scrape\Exception\NotFoundException;
use TreeHouse\IoBundle\Scrape\Exception\RateLimitException;
use TreeHouse\IoBundle\Scrape\Exception\UnexpectedResponseException;
use TreeHouse\IoBundle\Scrape\Handler\HandlerInterface;
use TreeHouse\IoBundle\Scrape\Parser\ParserInterface;
use TreeHouse\IoBundle\Scrape\Scraper;
use TreeHouse\IoBundle\Scrape\ScraperEvents;
use TreeHouse\IoBundle\Scrape\ScraperInterface;
use TreeHouse\IoBundle\Tests\Mock\SourceMock;

class ScraperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Scraper
     */
    protected $scraper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|CrawlerInterface
     */
    protected $crawler;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ParserInterface
     */
    protected $parser;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|HandlerInterface
     */
    protected $handler;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|EventDispatcherInterface
     */
    protected $dispatcher;

    protected function setUp()
    {
        $this->crawler    = $this->getMockForAbstractClass(CrawlerInterface::class);
        $this->parser     = $this->getMockForAbstractClass(ParserInterface::class);
        $this->handler    = $this->getMockForAbstractClass(HandlerInterface::class);
        $this->dispatcher = $this->getMockForAbstractClass(EventDispatcherInterface::class);

        $this->scraper = new Scraper($this->crawler, $this->parser, $this->handler, $this->dispatcher);
    }

    public function testConstructor()
    {
        $this->assertInstanceOf(ScraperInterface::class, $this->scraper);
    }

    public function testGetCrawler()
    {
        $this->assertInstanceOf(CrawlerInterface::class, $this->scraper->getCrawler());
    }

    public function testEventDispatcher()
    {
        $this->assertInstanceOf(EventDispatcherInterface::class, $this->scraper->getEventDispatcher());
    }

    public function testAsync()
    {
        $this->scraper->setAsync(true);
        $this->assertTrue($this->scraper->isAsync());

        $this->scraper->setAsync(false);
        $this->assertFalse($this->scraper->isAsync());
    }

    public function testScrapeItemSuccess()
    {
        $source = new SourceMock(1234);

        $this->crawler
            ->expects($this->once())
            ->method('getNextUrls')
            ->will($this->returnValue([]))
        ;

        $this->handler
            ->expects($this->once())
            ->method('handle')
            ->will($this->returnValue($source))
        ;

        $this->dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(ScraperEvents::ITEM_SUCCESS, $this->callback(function (SuccessItemEvent $event) use ($source) {
                return $event->getResult() === $source;
            }))
        ;

        $this->scraper->scrape(new ScraperEntity(), 'http://example.org');
    }

    public function testScrapeItemFiltered()
    {
        $this->crawler
            ->expects($this->once())
            ->method('getNextUrls')
            ->will($this->returnValue([]))
        ;

        $this->parser
            ->expects($this->once())
            ->method('parse')
            ->will($this->throwException(new FilterException()))
        ;

        $this->dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(ScraperEvents::ITEM_SKIPPED, $this->isInstanceOf(SkippedItemEvent::class));
        ;

        $this->scraper->scrape(new ScraperEntity(), 'http://example.org');
    }

    public function testScrapeItemInvalid()
    {
        $this->crawler
            ->expects($this->once())
            ->method('getNextUrls')
            ->will($this->returnValue([]))
        ;

        $this->parser
            ->expects($this->once())
            ->method('parse')
            ->will($this->throwException(new ValidationException()))
        ;

        $this->dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(ScraperEvents::ITEM_FAILED, $this->isInstanceOf(FailedItemEvent::class));
        ;

        $this->scraper->scrape(new ScraperEntity(), 'http://example.org');
    }

    public function testScrapeItemModificationFailure()
    {
        $this->crawler
            ->expects($this->once())
            ->method('getNextUrls')
            ->will($this->returnValue([]))
        ;

        $this->parser
            ->expects($this->once())
            ->method('parse')
            ->will($this->throwException(new ModificationException()))
        ;

        $this->dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(ScraperEvents::ITEM_FAILED, $this->isInstanceOf(FailedItemEvent::class));
        ;

        $this->scraper->scrape(new ScraperEntity(), 'http://example.org');
    }

    /**
     * @expectedException \TreeHouse\IoBundle\Scrape\Exception\RateLimitException
     */
    public function testScrapeRateLimit()
    {
        $this->crawler
            ->expects($this->once())
            ->method('crawl')
            ->will($this->throwException(new RateLimitException('http://example.org', '', new \DateTime())))
        ;

        $this->scraper->scrape(new ScraperEntity(), 'http://example.org');
    }

    /**
     * @expectedException \TreeHouse\IoBundle\Scrape\Exception\RateLimitException
     */
    public function testScrapeRateLimitAsync()
    {
        $this->scraper->setAsync(true);

        $this->crawler
            ->expects($this->once())
            ->method('crawl')
            ->will($this->throwException(new RateLimitException('http://example.org')))
        ;

        $this->dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(ScraperEvents::RATE_LIMIT_REACHED, $this->isInstanceOf(RateLimitEvent::class));
        ;

        $this->scraper->scrape(new ScraperEntity(), 'http://example.org');
    }

    /**
     * @expectedException \TreeHouse\IoBundle\Scrape\Exception\NotFoundException
     */
    public function testScrapeNotFoundResponse()
    {
        $this->crawler
            ->expects($this->once())
            ->method('crawl')
            ->will($this->throwException(new NotFoundException('http://example.org', new Response(410))))
        ;

        $this->dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(ScraperEvents::SCRAPE_URL_NOT_OK, $this->isInstanceOf(ScrapeResponseEvent::class));
        ;

        $this->scraper->scrape(new ScraperEntity(), 'http://example.org');
    }

    /**
     * @expectedException \TreeHouse\IoBundle\Scrape\Exception\UnexpectedResponseException
     */
    public function testScrapeUnexpectedResponse()
    {
        $this->crawler
            ->expects($this->once())
            ->method('crawl')
            ->will($this->throwException(new UnexpectedResponseException('http://example.org', new Response(403))))
        ;

        $this->dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(ScraperEvents::SCRAPE_URL_NOT_OK, $this->isInstanceOf(ScrapeResponseEvent::class));
        ;

        $this->scraper->scrape(new ScraperEntity(), 'http://example.org');
    }

    public function testScrapeAfter()
    {
        $this->dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(ScraperEvents::SCRAPE_NEXT_URL, $this->isInstanceOf(ScrapeUrlEvent::class));
        ;

        $this->scraper->scrapeAfter(new ScraperEntity(), 'http://example.org', new \DateTime());
    }

    public function testScrapeNext()
    {
        $entity = new ScraperEntity();
        $url    = 'http://www.treehouse.nl';

        $this->crawler
            ->expects($this->once())
            ->method('getNextUrls')
            ->will($this->returnValue([$url]))
        ;

        /** @var \PHPUnit_Framework_MockObject_MockObject|Scraper $scraper */
        $scraper = $this
            ->getMockBuilder(Scraper::class)
            ->setConstructorArgs([$this->crawler, $this->parser, $this->handler])
            ->setMethods(['scrape'])
            ->getMock()
        ;

        $scraper
            ->expects($this->once())
            ->method('scrape')
            ->with($entity, $url)
        ;

        $scraper->scrapeNext($entity);
    }

    public function testScrapeNextAsync()
    {
        $entity = new ScraperEntity();
        $url    = 'http://www.treehouse.nl';

        $this->crawler
            ->expects($this->once())
            ->method('getNextUrls')
            ->will($this->returnValue([$url]))
        ;

        /** @var \PHPUnit_Framework_MockObject_MockObject|Scraper $scraper */
        $scraper = $this
            ->getMockBuilder(Scraper::class)
            ->setConstructorArgs([$this->crawler, $this->parser, $this->handler])
            ->setMethods(['scrapeAfter'])
            ->getMock()
        ;

        $scraper
            ->expects($this->once())
            ->method('scrapeAfter')
            ->with($entity, $url, $this->greaterThanOrEqual(new \DateTime()))
        ;

        $scraper->setAsync(true);
        $scraper->scrapeNext($entity);
    }
}
