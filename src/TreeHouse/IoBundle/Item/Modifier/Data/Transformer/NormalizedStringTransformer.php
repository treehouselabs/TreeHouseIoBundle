<?php

namespace TreeHouse\IoBundle\Item\Modifier\Data\Transformer;

use TreeHouse\Feeder\Exception\TransformationFailedException;
use TreeHouse\Feeder\Modifier\Data\Transformer\TransformerInterface;

/**
 * Normalizes an input to be a guaranteed string
 */
class NormalizedStringTransformer implements TransformerInterface
{
    public function transform($value)
    {
        if (is_null($value)) {
            return $value;
        }

        if (is_scalar($value)) {
            $value = (string) $value;
        }

        if (!is_string($value)) {
            throw new TransformationFailedException(
                sprintf('Expected a string to transform, got %s instead', json_encode($value))
            );
        }

        // strip tags just to be sure
        return trim(strip_tags($value));
    }
}
