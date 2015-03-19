<?php

namespace TreeHouse\IoBundle\Tests\Scrape\Crawler\RateLimit;

use TreeHouse\IoBundle\Scrape\Crawler\Log\RequestLoggerInterface;
use TreeHouse\IoBundle\Scrape\Crawler\RateLimit\RpmRateLimit;

class RpmRateLimitTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $limit = new RpmRateLimit($this->getRequestLoggerMock(), 10);

        $this->assertInstanceOf(RpmRateLimit::class, $limit);
    }

    /**
     * @dataProvider getRateLimitData
     */
    public function testRateLimit($rpm, $count, $expected)
    {
        $requests = $count > 0 ? array_fill(0, $count, 'foo') : [];

        $logger = $this->getRequestLoggerMock();
        $logger
            ->expects($this->any())
            ->method('getRequestsSince')
            ->will($this->returnValue($requests))
        ;

        $limit = new RpmRateLimit($logger, $rpm);

        $this->assertSame($expected, $limit->limitReached());
        $this->assertGreaterThan(new \DateTime(), $limit->getRetryDate());
    }

    /**
     * @return array
     */
    public function getRateLimitData()
    {
        return [
            // less than 1 req/s
            [30, 0, false],
            [30, 1, true],

            // more than 1 req/s
            [240, 3, false],
            [240, 5, true],
        ];
    }

    public function testGetLimit()
    {
        $limit = new RpmRateLimit($this->getRequestLoggerMock(), 10);

        $this->assertEquals('10 requests/minute', $limit->getLimit());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|RequestLoggerInterface
     */
    private function getRequestLoggerMock()
    {
        return $this
            ->getMockBuilder(RequestLoggerInterface::class)
            ->getMockForAbstractClass()
        ;
    }
}
