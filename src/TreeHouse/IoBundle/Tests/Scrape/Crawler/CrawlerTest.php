<?php

namespace TreeHouse\IoBundle\Tests\Scrape\Crawler;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Message\ResponseInterface;
use GuzzleHttp\Stream\Stream;
use TreeHouse\IoBundle\Scrape\Crawler\AbstractCrawler;
use TreeHouse\IoBundle\Scrape\Crawler\Client\GuzzleClient;
use TreeHouse\IoBundle\Scrape\Crawler\CrawlerInterface;
use TreeHouse\IoBundle\Scrape\Crawler\Log\RequestLoggerInterface;
use TreeHouse\IoBundle\Scrape\Crawler\RateLimit\RateLimitInterface;

class CrawlerTest extends \PHPUnit_Framework_TestCase
{
    public function testClass()
    {
        $client    = new GuzzleClient($this->getMockForAbstractClass(ClientInterface::class));
        $logger    = $this->getMockForAbstractClass(RequestLoggerInterface::class);
        $rateLimit = $this->getMockForAbstractClass(RateLimitInterface::class);

        $crawler = new Crawler($client, $logger, $rateLimit);

        $this->assertInstanceOf(CrawlerInterface::class, $crawler);
        $this->assertSame($client, $crawler->getClient());
        $this->assertSame($logger, $crawler->getLogger());
        $this->assertSame($rateLimit, $crawler->getRateLimit());
    }

    public function testCrawl()
    {
        $content = 'Hello, World!';

        $guzzle = $this->getGuzzleMock(new Response(200, [], Stream::factory($content)));

        $logger = $this->getMockForAbstractClass(RequestLoggerInterface::class);
        $logger->expects($this->once())->method('logRequest');

        $rateLimit = $this->getMockForAbstractClass(RateLimitInterface::class);
        $rateLimit->expects($this->once())->method('limitReached');

        $crawler = $this->createCrawler($guzzle, $logger, $rateLimit);

        $this->assertEquals($content, $crawler->crawl('http://example.org'));

        $response = $crawler->getLastResponse();
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($content, $response->getContent());
    }

    public function testCrawlWithRedirect()
    {
        $url         = 'http://example.org';
        $redirectUrl = 'https://example.org/test';
        $content     = 'Hello, World!';

        $response = new Response(200, [], Stream::factory($content));
        $response->setEffectiveUrl($redirectUrl);

        $crawler = $this->createCrawler($this->getGuzzleMock($response));

        $this->assertEquals($content, $crawler->crawl($url));
        $this->assertEquals($redirectUrl, $crawler->getLastUrl());
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testLastResponseWithoutCrawl()
    {
        $client    = new GuzzleClient($this->getMockForAbstractClass(ClientInterface::class));
        $logger    = $this->getMockForAbstractClass(RequestLoggerInterface::class);
        $rateLimit = $this->getMockForAbstractClass(RateLimitInterface::class);

        $crawler = new Crawler($client, $logger, $rateLimit);
        $crawler->getLastResponse();
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testLastUrlWithoutCrawl()
    {
        $client    = new GuzzleClient($this->getMockForAbstractClass(ClientInterface::class));
        $logger    = $this->getMockForAbstractClass(RequestLoggerInterface::class);
        $rateLimit = $this->getMockForAbstractClass(RateLimitInterface::class);

        $crawler = new Crawler($client, $logger, $rateLimit);
        $crawler->getLastUrl();
    }

    /**
     * @expectedException \TreeHouse\IoBundle\Scrape\Exception\RateLimitException
     */
    public function testRateLimit()
    {
        $rateLimit = $this->getMockForAbstractClass(RateLimitInterface::class);
        $rateLimit
            ->expects($this->once())
            ->method('limitReached')
            ->will($this->returnValue(true))
        ;
        $rateLimit
            ->expects($this->once())
            ->method('getRetryDate')
            ->will($this->returnValue(new \DateTime('+1 second')))
        ;

        $crawler = $this->createCrawler(new Client(), null, $rateLimit);
        $crawler->crawl('http://example.org');
    }

    /**
     * @expectedException \TreeHouse\IoBundle\Scrape\Exception\RateLimitException
     */
    public function testRateLimitFromResponseWithTime()
    {
        $guzzle  = $this->getGuzzleMock(new Response(429, ['Retry-After' => 5]));
        $crawler = $this->createCrawler($guzzle);

        $crawler->crawl('http://example.org');
    }

    /**
     * @expectedException \TreeHouse\IoBundle\Scrape\Exception\RateLimitException
     */
    public function testRateLimitFromResponseWithDate()
    {
        $guzzle  = $this->getGuzzleMock(new Response(429, ['Retry-After' => (new \DateTime('+5 minutes'))->format(DATE_RFC2822)]));
        $crawler = $this->createCrawler($guzzle);

        $crawler->crawl('http://example.org');
    }

    /**
     * @expectedException \TreeHouse\IoBundle\Scrape\Exception\UnexpectedResponseException
     */
    public function testUnexpectedResponse()
    {
        $guzzle  = $this->getGuzzleMock(new Response(403));
        $crawler = $this->createCrawler($guzzle);

        $crawler->crawl('http://example.org');
    }

    /**
     * @param ResponseInterface $response
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|ClientInterface
     */
    protected function getGuzzleMock(ResponseInterface $response)
    {
        $guzzle = $this->getMockForAbstractClass(ClientInterface::class);
        $guzzle
            ->expects($this->once())
            ->method('get')
            ->will($this->returnValue($response))
        ;

        return $guzzle;
    }

    /**
     * @param ClientInterface $guzzle
     * @param mixed           $logger
     * @param mixed           $rateLimit
     *
     * @return Crawler
     */
    protected function createCrawler(ClientInterface $guzzle, $logger = null, $rateLimit = null)
    {
        $client    = new GuzzleClient($guzzle);
        $logger    = $logger ?: $this->getMockForAbstractClass(RequestLoggerInterface::class);
        $rateLimit = $rateLimit ?: $this->getMockForAbstractClass(RateLimitInterface::class);

        return new Crawler($client, $logger, $rateLimit);
    }
}

/**
 * @private
 */
class Crawler extends AbstractCrawler
{
    /**
     * @inheritdoc
     */
    public function getNextUrls()
    {
        return [];
    }
}
