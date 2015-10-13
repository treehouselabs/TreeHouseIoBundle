<?php

namespace TreeHouse\IoBundle\Import\Log;

use TreeHouse\IoBundle\Entity\Import;

class ArrayItemLogger extends AbstractItemLogger
{
    /**
     * @var array
     */
    protected $items = [];

    /**
     * @inheritdoc
     */
    public function getImportedItems(Import $import)
    {
        $ident = $this->getLogIdent($import);

        foreach ($this->items[$ident] as $item) {
            yield $item;
        }
    }

    /**
     * @inheritdoc
     */
    protected function doLog($ident, $originalId, array $context)
    {
        $this->items[$ident][$originalId] = $context;
    }

    /**
     * @inheritdoc
     */
    protected function doRemoveLog($ident)
    {
        unset($this->items[$ident]);
    }
}
