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
        $this->key   = $key;
    }

    /**
     * @inheritdoc
     */
    public function logRequest($url, \DateTime $date = null)
    {
        if (null === $date) {
            $date = new \DateTime();
        }

        $hashKey = $this->getHashKey($url);

        $this->redis->zAdd($this->key, $date->getTimestamp(), $hashKey);
    }

    /**
     * @inheritdoc
     */
    public function getRequestsSince(\DateTime $date)
    {
        $start = $date->getTimestamp();
        $end   = time();

        return array_map(
            function ($hash) {
                list (, $url) = explode('#', $hash, 2);

                return $url;
            },
            $this->redis->zRangeByScore($this->key, $start, $end)
        );
    }

    /**
     * @param string $url
     *
     * @return string
     */
    protected function getHashKey($url)
    {
        return sprintf('%d#%s', time(), $url);
    }
}
