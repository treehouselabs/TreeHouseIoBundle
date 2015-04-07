<?php

namespace TreeHouse\IoBundle\Tests\Scrape\Crawler\Log;

use TreeHouse\IoBundle\Scrape\Crawler\Log\RequestLoggerInterface;

abstract class AbstractRequestLoggerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RequestLoggerInterface
     */
    protected $logger;

    /**
     * @var array
     */
    protected $requests;

    protected function setUp()
    {
        $redis = new \Redis();
        $redis->connect('localhost');
        $redis->flushDB();

        $this->logger = $this->getLogger();

        $this->requests = [
            strtotime('-1 minute')  => 'http://example/org/1',
            strtotime('-2 minutes') => 'http://example/org/2',
            strtotime('-5 minutes') => 'http://example/org/3',
            strtotime('-6 minutes') => 'http://example/org/4',
            strtotime('-7 minutes') => 'http://example/org/5',
            strtotime('-8 minutes') => 'http://example/org/6',
            strtotime('-9 minutes') => 'http://example/org/7',
        ];

        foreach ($this->requests as $time => $url) {
            $this->logger->logRequest($url, new \DateTime('@' . $time));
        }
    }

    /**
     * Tests a slice of the logged requests
     */
    public function testLoggedRequest()
    {
        $this->assertEquals(
            $this->getLoggedRequests(array_slice($this->requests, 0, 3, true)),
            $this->logger->getRequestsSince(new \DateTime('-5 minutes'))
        );
    }

    /**
     * Tests an interval in which no requests were logged
     */
    public function testNoLoggedRequests()
    {
        $this->assertEmpty($this->logger->getRequestsSince(new \DateTime('-30 seconds')));
    }

    /**
     * Tests fetching of all logged requests
     */
    public function testAllLoggedRequests()
    {
        $this->assertEquals(
            $this->getLoggedRequests($this->requests),
            $this->logger->getRequestsSince()
        );
    }

    /**
     * @param array $requests
     *
     * @return array
     */
    protected function getLoggedRequests(array $requests)
    {
        $reqs = [];

        foreach (array_reverse($requests, true) as $time => $url) {
            $reqs[] = [$time, $url];
        }

        return $reqs;
    }

    /**
     * @return RequestLoggerInterface
     */
    abstract protected function getLogger();
}
