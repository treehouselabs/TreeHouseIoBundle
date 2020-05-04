<?php

namespace TreeHouse\IoBundle\Item\Modifier\Data\Transformer;

use DateTime;
use TreeHouse\Feeder\Exception\TransformationFailedException;
use TreeHouse\Feeder\Modifier\Data\Transformer\TransformerInterface;

class StringToDateTimeTransformer implements TransformerInterface
{
    /** @var \DateTimeZone */
    private $timezone;
    /** @var int */
    private $minYear;

    /**
     * @param \DateTimeZone $timezone
     */
    public function __construct(\DateTimeZone $timezone = null, int $minYear = 1900)
    {
        $this->timezone = $timezone ?: new \DateTimeZone('UTC');
        $this->minYear = $minYear;
    }

    /**
     * @inheritdoc
     */
    public function transform($value)
    {
        if (empty($value)) {
            return null;
        }

        if ($value instanceof DateTime) {
            return $value;
        }

        try {
            $date = new DateTime($value, $this->timezone);
            if ((int) $date->format('Y') < $this->minYear) {
                throw new TransformationFailedException(
                    "The given date was older than the minimum year of {$this->minYear}: {$value}"
                );
            }

            return $date;
        } catch (\Exception $exception) {
            throw new TransformationFailedException(
                $exception->getMessage()
            );
        }
    }
}
