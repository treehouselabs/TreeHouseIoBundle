<?php

namespace TreeHouse\IoBundle\Import\Log;

use TreeHouse\IoBundle\Entity\Import;

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
    public function getImportedItems(Import $import)
    {
        $ident = $this->getLogIdent($import);

        $cursor = null;
        do {
            $items = $this->redis->hScan($ident, $cursor);

            foreach ($items as $item) {
                yield json_decode($item, true);
            }
        } while ($cursor > 0);
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
