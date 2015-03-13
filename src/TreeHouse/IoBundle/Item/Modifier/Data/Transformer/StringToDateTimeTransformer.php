<?php

namespace TreeHouse\IoBundle\Item\Modifier\Data\Transformer;

use TreeHouse\Feeder\Modifier\Data\Transformer\LocalizedStringToDateTimeTransformer;
use TreeHouse\Feeder\Modifier\Data\Transformer\TransformerInterface;

class StringToDateTimeTransformer implements TransformerInterface
{
    /**
     * @var \DateTimeZone
     */
    protected $timezone;

    /**
     * @param \DateTimeZone $timezone
     */
    public function __construct(\DateTimeZone $timezone = null)
    {
        $this->timezone = $timezone ?: new \DateTimeZone('UTC');
    }

    /**
     * @inheritdoc
     */
    public function transform($value)
    {
        if (is_null($value) || empty($value)) {
            return;
        }

        if ($value instanceof \DateTime) {
            return $value;
        }

        // check if strtotime matches
        if (false !== strtotime($value)) {
            return new \DateTime($value, $this->timezone);
        }

        // last resort
        $transformer = new LocalizedStringToDateTimeTransformer();

        return $transformer->transform($value);
    }
}
