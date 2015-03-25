<?php

namespace TreeHouse\IoBundle\Tests\Scrape\Crawler\Client;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Message\Response as GuzzleResponse;
use GuzzleHttp\Stream\Stream;
use Symfony\Component\HttpFoundation\Response;
use TreeHouse\IoBundle\Scrape\Crawler\Client\GuzzleClient;

class GuzzleClientTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var GuzzleClient
     */
    protected $client;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ClientInterface
     */
    protected $guzzle;

    protected function setUp()
    {
        $this->guzzle = $this->getMockForAbstractClass(ClientInterface::class);
        $this->client = new GuzzleClient($this->guzzle);
    }

    public function testFetch()
    {
        $url      = 'http://google.com';
        $redirect = 'https://www.google.com';

        $response = new GuzzleResponse(200, [], Stream::factory('Don\'t be evil'));
        $response->setEffectiveUrl($redirect);

        $this->guzzle
            ->expects($this->once())
            ->method('get')
            ->will($this->returnValue($response))
        ;

        $result = $this->client->fetch($url);

        $this->assertInternalType('array', $result);
        $this->assertCount(2, $result);

        list($effectiveUrl, $response) = $result;

        $this->assertEquals($redirect, $effectiveUrl);
        $this->assertInstanceOf(Response::class, $response);
    }

    public function testFetchWithUserAgent()
    {
        $url = 'http://www.google.com';
        $ua = 'IO Crawler/1.0';

        $body     = 'test';
        $headers  = ['content-length' => [1234], 'content-type' => ['text/plain']];
        $response = new GuzzleResponse(200, $headers, Stream::factory($body));

        $guzzle = $this->getMockForAbstractClass(ClientInterface::class);
        $guzzle
            ->expects($this->once())
            ->method('get')
            ->with($url, ['headers' => ['User-Agent' => $ua]])
            ->will($this->returnValue($response))
        ;

        $client = new GuzzleClient($guzzle);

        /** @var Response $response */
        list(, $response) = $client->fetch($url, $ua);

        $this->assertArraySubset($headers, $response->headers->all());
        $this->assertEquals($body, $response->getContent());
    }
}
