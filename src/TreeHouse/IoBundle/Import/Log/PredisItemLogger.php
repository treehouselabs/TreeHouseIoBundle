<?php

namespace TreeHouse\IoBundle\Import\Log;

use Predis\Client as Predis;

class PredisItemLogger extends AbstractItemLogger
{
    /**
     * @var Predis
     */
    protected $predis;

    /**
     * @param Predis $predis
     */
    public function __construct(Predis $predis)
    {
        $this->predis = $predis;
    }

    /**
     * @inheritdoc
     */
    protected function doLog($ident, $originalId, array $context)
    {
        $this->predis->hset($ident, $originalId, json_encode($context));
    }

    /**
     * @inheritdoc
     */
    protected function doRemoveLog($ident)
    {
        $this->predis->del($ident);
    }
}
