<?php

namespace TreeHouse\IoBundle\Tests\Scrape\Crawler\Client;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Psr\Http\Message\ResponseInterface;
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
        $url = 'http://google.com';
        $redirect = 'https://www.google.com';
        $ua = 'IO Crawler/1.0';

        $body = 'test';
        $headers = ['content-length' => [1234], 'content-type' => ['text/plain']];
        $response = new GuzzleResponse(200, $headers, $body);

        $this->guzzle
            ->expects($this->once())
            ->method('request')
            ->with('GET', $url, $this->callback(function (array $options) use ($ua) {
                $this->assertArraySubset(['headers' => ['User-Agent' => $ua]], $options);

                return true;
            }))
            ->will($this->returnCallback(function () use ($url, $redirect, $response) {
                $this->client->setEffectiveUri($url, $redirect);

                return $response;
            }))
        ;

        $result = $this->client->fetch($url, $ua);

        $this->assertInternalType('array', $result);
        $this->assertCount(2, $result);

        /** @var ResponseInterface $response */
        list($effectiveUrl, $response) = $result;

        $this->assertEquals($redirect, $effectiveUrl);
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertArraySubset($headers, $response->getHeaders());
        $this->assertEquals($body, $response->getBody()->getContents());
    }
}
