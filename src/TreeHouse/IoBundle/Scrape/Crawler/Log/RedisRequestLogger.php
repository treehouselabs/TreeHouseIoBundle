<?php

namespace TreeHouse\IoBundle\Scrape\Crawler\Log;

class RedisRequestLogger implements RequestLoggerInterface
{
    /**
     * @var \Redis
     */
    protected $redis;

    /**
     * @var string
     */
    protected $key;

    /**
     * @param \Redis $redis
     * @param string $key
     */
    public function __construct(\Redis $redis, $key = 'crawler_requests')
    {
        $this->redis = $redis;
        $this->key = $key;
    }

    /**
     * @inheritdoc
     */
    public function logRequest($url, \DateTime $date = null)
    {
        if (null === $date) {
            $date = new \DateTime();
        }

        $timestamp = $date->getTimestamp();
        $hashKey = $this->getHashKey($timestamp, $url);

        $this->redis->zAdd($this->key, $timestamp, $hashKey);
    }

    /**
     * @inheritdoc
     */
    public function getRequestsSince(\DateTime $date = null)
    {
        $start = $date ? $date->getTimestamp() : '-inf';
        $end = time();

        return array_map(
            function ($hash) {
                list($timestamp, $url) = explode('#', $hash, 2);

                return [intval($timestamp), $url];
            },
            $this->redis->zRangeByScore($this->key, $start, $end)
        );
    }

    /**
     * @param int    $timestamp
     * @param string $url
     *
     * @return string
     */
    protected function getHashKey($timestamp, $url)
    {
        return sprintf('%d#%s', $timestamp, $url);
    }
}
