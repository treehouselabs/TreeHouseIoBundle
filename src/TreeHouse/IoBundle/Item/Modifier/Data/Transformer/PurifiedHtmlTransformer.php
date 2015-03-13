<?php

namespace TreeHouse\IoBundle\Item\Modifier\Data\Transformer;

use TreeHouse\Feeder\Exception\TransformationFailedException;
use TreeHouse\Feeder\Modifier\Data\Transformer\TransformerInterface;

class PurifiedHtmlTransformer implements TransformerInterface
{
    /**
     * @var \HTMLPurifier
     */
    protected $purifier;

    /**
     * @param \HTMLPurifier $purifier
     */
    public function __construct(\HTMLPurifier $purifier)
    {
        $this->purifier  = $purifier;
    }

    /**
     * @inheritdoc
     */
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

        // purify to remove really obscure html
        return $this->purifier->purify($value);
    }
}
