<?php

namespace TreeHouse\IoBundle\Tests\Scrape\Crawler\Log;

use TreeHouse\IoBundle\Scrape\Crawler\Log\ArrayRequestLogger;

class ArrayRequestLoggerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ArrayRequestLogger
     */
    protected $logger;

    protected function setUp()
    {
        $this->logger = new ArrayRequestLogger();
    }

    public function testLoggedRequest()
    {
        $url  = 'http://example.org';
        $date = new \DateTime('-5 minutes');

        $this->logger->logRequest($url, $date);

        $requests = $this->logger->getRequestsSince($date);

        $this->assertEquals([$url], $requests);
    }

    public function testNoLoggedRequests()
    {
        $url  = 'http://example.org';
        $date = new \DateTime('-5 minutes');

        $this->logger->logRequest($url, $date);

        $requests = $this->logger->getRequestsSince(new \DateTime('-4 minutes'));

        $this->assertEmpty($requests);
    }
}
