<?php

namespace TreeHouse\IoBundle\Tests\Scrape\Crawler;

use GuzzleHttp\Psr7\Response;
use TreeHouse\IoBundle\Scrape\Crawler\AbstractCrawler;
use TreeHouse\IoBundle\Scrape\Crawler\Client\GuzzleClient;
use TreeHouse\IoBundle\Scrape\Crawler\CrawlerInterface;
use TreeHouse\IoBundle\Scrape\Crawler\Log\RequestLoggerInterface;
use TreeHouse\IoBundle\Scrape\Crawler\RateLimit\RateLimitInterface;

class CrawlerTest extends \PHPUnit_Framework_TestCase
{
    public function testClass()
    {
        $client = $this->getClientMock();
        $logger = $this->getLoggerMock();
        $rateLimit = $this->getRateLimitMock();

        $crawler = new Crawler($client, $logger, $rateLimit);

        $this->assertInstanceOf(CrawlerInterface::class, $crawler);
        $this->assertSame($client, $crawler->getClient());
        $this->assertSame($logger, $crawler->getLogger());
        $this->assertSame($rateLimit, $crawler->getRateLimit());
    }

    public function testCrawl()
    {
        $url = 'http://example.org';
        $content = 'Hello, World!';

        $client = $this->getClientMock();
        $client
            ->expects($this->once())
            ->method('fetch')
            ->willReturn([$url, new Response(200, [], $content)])
        ;

        $logger = $this->getLoggerMock();
        $logger->expects($this->once())->method('logRequest');

        $rateLimit = $this->getRateLimitMock();
        $rateLimit->expects($this->once())->method('limitReached');

        $crawler = $this->createCrawler($client, $logger, $rateLimit);

        $this->assertEquals($content, $crawler->crawl($url));

        $response = $crawler->getLastResponse();
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($content, $response->getBody()->getContents());
    }

    public function testCrawlWithRedirect()
    {
        $url = 'http://example.org';
        $redirectUrl = 'https://example.org/test';
        $content = 'Hello, World!';

        $response = new Response(200, [], $content);

        $logger = $this->getLoggerMock();
        $rateLimit = $this->getRateLimitMock();
        $client = $this->getClientMock();
        $client->expects($this->once())->method('fetch')->willReturn([$redirectUrl, $response]);

        $crawler = new Crawler($client, $logger, $rateLimit);

        $response = $crawler->crawl($url);
        $crawler->redirect($redirectUrl);

        $this->assertEquals($content, $response);
        $this->assertEquals($redirectUrl, $crawler->getLastUrl());
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testLastResponseWithoutCrawl()
    {
        $client = $this->getClientMock();
        $logger = $this->getLoggerMock();
        $rateLimit = $this->getRateLimitMock();

        $crawler = new Crawler($client, $logger, $rateLimit);
        $crawler->getLastResponse();
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testLastUrlWithoutCrawl()
    {
        $client = $this->getClientMock();
        $logger = $this->getLoggerMock();
        $rateLimit = $this->getRateLimitMock();

        $crawler = new Crawler($client, $logger, $rateLimit);
        $crawler->getLastUrl();
    }

    /**
     * @expectedException \TreeHouse\IoBundle\Scrape\Exception\RateLimitException
     */
    public function testRateLimit()
    {
        $rateLimit = $this->getRateLimitMock();
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

        $crawler = $this->createCrawler($this->getClientMock(), null, $rateLimit);
        $crawler->crawl('http://example.org');
    }

    /**
     * @expectedException \TreeHouse\IoBundle\Scrape\Exception\RateLimitException
     */
    public function testRateLimitFromResponseWithTime()
    {
        $url = 'http://example.org';

        $client = $this->getClientMock();
        $client
            ->expects($this->once())
            ->method('fetch')
            ->willReturn([$url, new Response(429, ['Retry-After' => 5])])
        ;

        $crawler = $this->createCrawler($client);
        $crawler->crawl($url);
    }

    /**
     * @expectedException \TreeHouse\IoBundle\Scrape\Exception\RateLimitException
     */
    public function testRateLimitFromResponseWithDate()
    {
        $url = 'http://example.org';

        $client = $this->getClientMock();
        $client
            ->expects($this->once())
            ->method('fetch')
            ->willReturn([$url, new Response(429, ['Retry-After' => date(DATE_RFC2822, strtotime('+5 minutes'))])])
        ;

        $crawler = $this->createCrawler($client);
        $crawler->crawl($url);
    }

    /**
     * @expectedException \TreeHouse\IoBundle\Scrape\Exception\UnexpectedResponseException
     */
    public function testUnexpectedResponse()
    {
        $url = 'http://example.org';

        $client = $this->getClientMock();
        $client
            ->expects($this->once())
            ->method('fetch')
            ->willReturn([$url, new Response(403)])
        ;

        $crawler = $this->createCrawler($client);
        $crawler->crawl($url);
    }

    /**
     * @param GuzzleClient $client
     * @param mixed        $logger
     * @param mixed        $rateLimit
     *
     * @return Crawler
     */
    private function createCrawler(GuzzleClient $client, $logger = null, $rateLimit = null)
    {
        $logger = $logger ?: $this->getLoggerMock();
        $rateLimit = $rateLimit ?: $this->getRateLimitMock();

        return new Crawler($client, $logger, $rateLimit);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|GuzzleClient
     */
    private function getClientMock()
    {
        return $this
            ->getMockBuilder(GuzzleClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['fetch'])
            ->getMock()
        ;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|RequestLoggerInterface
     */
    private function getLoggerMock()
    {
        return $this->getMockForAbstractClass(RequestLoggerInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|RateLimitInterface
     */
    private function getRateLimitMock()
    {
        return $this->getMockForAbstractClass(RateLimitInterface::class);
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

    public function redirect($url)
    {
        $this->url = $url;
    }
}
