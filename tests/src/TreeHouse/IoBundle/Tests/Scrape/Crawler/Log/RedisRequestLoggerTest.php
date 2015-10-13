<?php

namespace TreeHouse\IoBundle\Tests\Scrape\Crawler\Log;

use TreeHouse\IoBundle\Scrape\Crawler\Log\RedisRequestLogger;

class RedisRequestLoggerTest extends AbstractRequestLoggerTest
{
    /**
     * @inheritdoc
     */
    protected function getLogger()
    {
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('Redis extension not loaded');
        }

        $redis = new \Redis();

        if (false === $redis->connect('localhost', 6379, 1)) {
            $this->markTestSkipped('Could not connect to Redis server');
        }

        $redis->flushDB();

        return new RedisRequestLogger($redis);
    }
}
