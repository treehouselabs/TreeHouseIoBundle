<?php

namespace TreeHouse\IoBundle\Item\Modifier\Data\Transformer;

use TreeHouse\Feeder\Exception\TransformationFailedException;
use TreeHouse\Feeder\Modifier\Data\Transformer\LocalizedStringToNumberTransformer as BaseTransformer;

/**
 * Transforms a string into a number. This one is a bit more lenient than the
 * base number transformer, as it tries to match a number in a string first, and
 * then transforms that into a number.
 */
class LocalizedStringToNumberTransformer extends BaseTransformer
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

        // make sure grouping is not used
        $this->grouping = false;

        $formatter = $this->getNumberFormatter();
        $groupSep  = $formatter->getSymbol(\NumberFormatter::GROUPING_SEPARATOR_SYMBOL);
        $decSep    = $formatter->getSymbol(\NumberFormatter::DECIMAL_SEPARATOR_SYMBOL);

        // remove grouping separator
        $value = str_replace($groupSep, '', $value);

        // try to match something like 1234 or 1234<dec>45
        // discard alphanumeric characters altogether
        if (!preg_match('/(\-?\d+('.preg_quote($decSep).'\d+)?)/', $value, $matches)) {
            // could not find any digit
            return;
        }

        // use the matched numbers as value
        $value = $matches[1];

        return parent::transform($value);
    }
}
