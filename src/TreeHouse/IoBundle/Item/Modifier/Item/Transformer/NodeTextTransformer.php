<?php

namespace TreeHouse\IoBundle\Item\Modifier\Item\Transformer;

use Symfony\Component\HttpFoundation\ParameterBag;
use TreeHouse\Feeder\Modifier\Item\Transformer\TransformerInterface;

class NodeTextTransformer implements TransformerInterface
{
    public function transform(ParameterBag $item)
    {
        foreach ($item->all() as $key => $value) {
            // if value is an array with a hash, that's a serialized node's text value
            if (is_array($value) && array_key_exists('#', $value)) {
                $item->set($key, $value['#']);
            }
        }
    }
}
