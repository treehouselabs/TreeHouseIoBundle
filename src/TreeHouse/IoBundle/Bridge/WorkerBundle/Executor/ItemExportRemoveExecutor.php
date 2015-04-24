<?php

namespace TreeHouse\IoBundle\Bridge\WorkerBundle\Executor;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use TreeHouse\IoBundle\Export\FeedExporter;
use TreeHouse\WorkerBundle\Executor\AbstractExecutor;
use TreeHouse\WorkerBundle\Executor\ObjectPayloadInterface;

class ItemExportRemoveExecutor extends AbstractExecutor implements ObjectPayloadInterface
{
    const NAME = 'item.export.remove';

    /**
     * @var FeedExporter
     */
    protected $exporter;

    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    /**
     * @param FeedExporter    $exporter
     * @param ManagerRegistry $doctrine
     */
    public function __construct(FeedExporter $exporter, ManagerRegistry $doctrine)
    {
        $this->exporter = $exporter;
        $this->doctrine = $doctrine;
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @inheritdoc
     */
    public function supportsObject($object)
    {
        return $this->exporter->supports($object);
    }

    /**
     * @inheritdoc
     */
    public function getObjectPayload($object)
    {
        $class = get_class($object);
        $meta  = $this->doctrine->getManagerForClass($class)->getClassMetadata($class);

        return [$class, $meta->getIdentifierValues($object)];
    }

    /**
     * @inheritdoc
     */
    public function configurePayload(OptionsResolver $resolver)
    {
        $resolver->setRequired(0);
        $resolver->setRequired(1);
        $resolver->setAllowedTypes(0, 'string');
        $resolver->setAllowedTypes(1, 'array');
        $resolver->setNormalizer(1, function (Options $options, $value) {
            $class = $options[0];

            // use a reference if the item does not exist anymore:
            // maybe we're cleaning up after it's been removed
            if (null === $item = $this->doctrine->getRepository($class)->findOneBy($value)) {
                /** @var EntityManagerInterface $manager */
                $manager = $this->doctrine->getManagerForClass($class);
                $item = $manager->getReference($class, $value);
            }

            return $item;
        });
    }

    /**
     * @inheritdoc
     */
    public function execute(array $payload)
    {
        $item = $payload[1];

        // remove export directory
        foreach ($this->exporter->getTypes() as $type) {
            if ($type->supports($item)) {
                $dir = dirname($this->exporter->getItemCacheFilename($item, $type));
                (new Filesystem())->remove($dir);
            }
        }

        return true;
    }
}
