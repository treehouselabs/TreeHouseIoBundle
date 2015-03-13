<?php

namespace TreeHouse\IoBundle\Item\Modifier\Data\Transformer;

use TreeHouse\Feeder\Exception\TransformationFailedException;
use TreeHouse\Feeder\Modifier\Data\Transformer\TransformerInterface;

class ArrayToStringTransformer implements TransformerInterface
{
    /**
     * @var string
     */
    protected $glue;

    /**
     * @param string $glue
     */
    public function __construct($glue = "\n\n")
    {
        $this->glue = $glue;
    }

    public function transform($value)
    {
        if (is_null($value) || is_string($value)) {
            return $value;
        }

        // bail out when it's not an array
        if (!is_array($value) && !$value instanceof \Traversable) {
            throw new TransformationFailedException(
                sprintf('Expected a traversable or a string to transform, got %s instead', gettype($value))
            );
        }

        return $this->implodeRecursive($value, $this->glue);
    }

    /**
     * @param  array|\Traversable $array
     * @param  string             $glue
     * @return string
     */
    protected function implodeRecursive($array, $glue)
    {
        $parts = [];

        foreach ($array as $item) {
            if (is_array($item)) {
                $parts[] = $this->implodeRecursive($item, $glue);
            } else {
                $parts[] = $this->toString($item);
            }
        }

        return implode($glue, $parts);
    }

    /**
     * Casts/converts given item to a string, if possible
     *
     * @param  mixed                         $item
     * @return string
     * @throws TransformationFailedException
     */
    protected function toString($item)
    {
        if (is_object($item)) {
            if (!method_exists($item, '__toString')) {
                throw new TransformationFailedException(
                    sprintf('Cannot transform "%s" object to string', get_class($item))
                );
            }

            $item = (string) $item;
        }

        if (!is_scalar($item)) {
            throw new TransformationFailedException(
                sprintf('Cannot transform "%s" to string', gettype($item))
            );
        }

        return (string) $item;
    }
}
