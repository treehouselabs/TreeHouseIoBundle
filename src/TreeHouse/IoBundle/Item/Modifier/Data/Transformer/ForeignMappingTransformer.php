<?php

namespace TreeHouse\IoBundle\Item\Modifier\Data\Transformer;

use TreeHouse\Feeder\Exception\TransformationFailedException;
use TreeHouse\Feeder\Modifier\Data\Transformer\TransformerInterface;

class ForeignMappingTransformer implements TransformerInterface
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var array
     */
    protected $mapping;

    /**
     * @param string $name
     * @param array  $mapping
     */
    public function __construct($name, array $mapping)
    {
        $this->name    = $name;
        $this->mapping = $mapping;
    }

    public function transform($value)
    {
        if (is_null($value)) {
            return $value;
        }

        if (is_scalar($value)) {
            return $this->getMappedValue($value);
        }

        if (is_array($value)) {
            $newValue = [];

            foreach ($value as $val) {
                if (!is_array($val)) {
                    $val = [$val];
                }
                foreach ($val as $val2) {
                    $newValue[] = $this->getMappedValue($val2);
                }
            }

            return array_filter($newValue);
        }

        throw new TransformationFailedException(
            sprintf(
                'Expected a scalar value or an array of scalar values to transform, got %s instead',
                json_encode($value)
            )
        );
    }

    /**
     * @param string $value
     *
     * @throws TransformationFailedException
     *
     * @return string
     */
    protected function getMappedValue($value)
    {
        if (null === $value) {
            return $value;
        }

        if (!array_key_exists($value, $this->mapping)) {
            throw new TransformationFailedException(
                sprintf('Value %s not found for key "%s"', json_encode($value), $this->name)
            );
        }

        return $this->mapping[$value];
    }
}
