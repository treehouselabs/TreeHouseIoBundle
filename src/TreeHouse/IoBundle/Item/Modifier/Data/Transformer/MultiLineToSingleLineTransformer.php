<?php

namespace TreeHouse\IoBundle\Item\Modifier\Data\Transformer;

use Symfony\Component\HttpFoundation\ParameterBag;
use TreeHouse\Feeder\Exception\TransformationFailedException;
use TreeHouse\Feeder\Modifier\Data\Transformer\TransformerInterface;

class MultiLineToSingleLineTransformer implements TransformerInterface
{
    public function transform($value, $key = null, ParameterBag $item = null)
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

        $value = preg_replace('/\n{1,}/', " ", $value); // replace newlines with single newline
        $value = preg_replace('/ {2,}/', " ", $value);  // replace double spaces with single space
        $value = trim($value);                          // trim leading and trailing spaces

        return $value;
    }
}
