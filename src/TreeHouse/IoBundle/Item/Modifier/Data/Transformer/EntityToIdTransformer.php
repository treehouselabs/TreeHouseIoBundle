<?php

namespace TreeHouse\IoBundle\Item\Modifier\Data\Transformer;

use Doctrine\Common\Persistence\Proxy;
use Doctrine\ORM\EntityManagerInterface;
use TreeHouse\Feeder\Exception\TransformationFailedException;
use TreeHouse\Feeder\Modifier\Data\Transformer\TransformerInterface;

class EntityToIdTransformer implements TransformerInterface
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function transform($value)
    {
        // already transformed (or null)
        if (is_array($value) || is_null($value)) {
            return $value;
        }

        if (!is_object($value)) {
            throw new TransformationFailedException(
                sprintf('Expected an object to transform, got "%s"', json_encode($value))
            );
        }

        // load object if it's a Proxy
        if ($value instanceof Proxy) {
            $value->__load();
        }

        $meta = $this->entityManager->getClassMetadata(get_class($value));

        return $meta->getIdentifierValues($value);
    }
}
