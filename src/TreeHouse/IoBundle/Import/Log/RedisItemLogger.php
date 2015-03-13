<?php

namespace TreeHouse\IoBundle\Import\Log;

class RedisItemLogger extends AbstractItemLogger
{
    /**
     * @var \Redis
     */
    protected $redis;

    /**
     * @param \Redis $redis
     */
    public function __construct(\Redis $redis)
    {
        $this->redis = $redis;
    }

    /**
     * @inheritdoc
     */
    protected function doLog($ident, $originalId, array $context)
    {
        $this->redis->hset($ident, $originalId, json_encode($context));
    }

    /**
     * @inheritdoc
     */
    protected function doRemoveLog($ident)
    {
        $this->redis->del($ident);
    }
}
