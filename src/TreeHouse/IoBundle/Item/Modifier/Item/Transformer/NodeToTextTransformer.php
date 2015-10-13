<?php

namespace TreeHouse\IoBundle\Item\Modifier\Item\Transformer;

use Symfony\Component\HttpFoundation\ParameterBag;
use TreeHouse\Feeder\Exception\UnexpectedTypeException;
use TreeHouse\Feeder\Modifier\Item\Transformer\TransformerInterface;

/**
 * Transforms serialized (XML) node into a text field:.
 *
 * Example:
 *
 * ```
 * link => [
 *   rel => external
 *   #   => http://example.org
 * ]
 * ```
 *
 * becomes:
 *
 * ```
 * link => http://example.org
 * ```
 */
class NodeToTextTransformer implements TransformerInterface
{
    /**
     * @var string
     */
    protected $field;

    /**
     * @param string $field Transform a specific field, if omitted, all root-level fields are transformed
     *
     * @throws UnexpectedTypeException
     */
    public function __construct($field = null)
    {
        if (!is_string($field) && !is_null($field)) {
            throw new UnexpectedTypeException($field, 'string or null');
        }

        $this->field = $field;
    }

    /**
     * @inheritdoc
     */
    public function transform(ParameterBag $item)
    {
        $keys = $this->field ? [$this->field] : $item->keys();

        $this->replace($item, $keys);
    }

    /**
     * @param ParameterBag $item
     * @param array        $keys
     */
    protected function replace(ParameterBag $item, array $keys)
    {
        foreach ($keys as $key) {
            if (!$item->has($key)) {
                continue;
            }

            $value = $item->get($key);

            // if value is an array with a hash, that's a serialized node's text value
            if (is_array($value) && array_key_exists('#', $value)) {
                $item->set($key, $value['#']);
            }
        }
    }
}
