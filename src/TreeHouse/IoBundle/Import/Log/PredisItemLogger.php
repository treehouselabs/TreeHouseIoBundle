<?php

namespace TreeHouse\IoBundle\Import\Log;

use Predis\Client as Predis;
use TreeHouse\IoBundle\Entity\Import;

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
    public function getImportedItems(Import $import)
    {
        $ident = $this->getLogIdent($import);

        $cursor = null;
        do {
            $items = $this->predis->hscan($ident, $cursor);

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
