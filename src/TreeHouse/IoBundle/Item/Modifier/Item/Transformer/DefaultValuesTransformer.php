<?php

namespace TreeHouse\IoBundle\Item\Modifier\Item\Transformer;

use Symfony\Component\HttpFoundation\ParameterBag;
use TreeHouse\Feeder\Modifier\Data\Transformer\EmptyValueToNullTransformer;
use TreeHouse\Feeder\Modifier\Item\Transformer\TransformerInterface;

/**
 * Sets default values when they are not present or empty in feed.
 */
class DefaultValuesTransformer implements TransformerInterface
{
    /**
     * @var bool indicates if values should be overwritten if they exist
     */
    protected $overwrite;

    /**
     * @var array
     */
    protected $defaultValues;

    /**
     * @param array $defaultValues
     * @param bool  $overwrite     indicates if values should be overwritten if they exist
     */
    public function __construct(array $defaultValues, $overwrite = false)
    {
        $this->defaultValues = $defaultValues;
        $this->overwrite = $overwrite;
    }

    /**
     * {@inheritdoc}
     */
    public function transform(ParameterBag $item)
    {
        $transformer = new EmptyValueToNullTransformer();

        // check for any default values that could be set
        foreach ($this->defaultValues as $key => $value) {
            // only set when it's not existing in the data or has no value
            if (!$item->has($key) || null === $transformer->transform($item->get($key), $key, $item) || $this->overwrite) {
                $item->set($key, $value);
            }
        }
    }
}
