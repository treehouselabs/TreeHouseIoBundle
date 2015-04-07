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
        $redis = new \Redis();
        $redis->connect('localhost');

        return new RedisRequestLogger($redis);
    }
}
