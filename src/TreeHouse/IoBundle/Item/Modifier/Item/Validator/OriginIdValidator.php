<?php

namespace TreeHouse\IoBundle\Item\Modifier\Item\Validator;

use Symfony\Component\HttpFoundation\ParameterBag;
use TreeHouse\Feeder\Exception\ValidationException;
use TreeHouse\Feeder\Modifier\Item\Validator\ValidatorInterface;
use TreeHouse\IoBundle\Import\Model\FeedItemBag;

/**
 * Validates an origin for a valid external id.
 * The id is considered valid if it is one of:
 *
 * * a positive integer
 * * a non-empty string without whitespace
 */
class OriginIdValidator implements ValidatorInterface
{
    public function validate(ParameterBag $item)
    {
        /** @var FeedItemBag $item */
        $originalId = $item->getOriginalId();

        // non-scalar values
        if (!is_scalar($originalId)) {
            throw new ValidationException(
                sprintf('Non-scalar value for original_id encountered: "%s"', var_export($originalId, true))
            );
        }

        $originalId = (string) $originalId;

        // non-positive numerical values
        if ((is_numeric($originalId) && (intval($originalId) < 1))) {
            throw new ValidationException(sprintf('Non-positive value for original_id: "%s"', $originalId));
        }

        // empty values or values containing whitespace
        if (empty(trim($originalId))) {
            throw new ValidationException(sprintf('Invalid original_id: "%s"', $originalId));
        }
    }
}
