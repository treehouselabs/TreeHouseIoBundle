<?php

namespace TreeHouse\IoBundle\Bridge\WorkerBundle\Executor;

use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\OptionsResolver\Exception\InvalidArgumentException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use TreeHouse\WorkerBundle\Executor\AbstractExecutor;
use TreeHouse\IoBundle\Export\FeedExporter;

class ExportItemExecutor extends AbstractExecutor
{
    const NAME = "export.item";

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
    public function configurePayload(OptionsResolver $resolver)
    {
        $resolver->setRequired(0);
        $resolver->setRequired(1);
        $resolver->setAllowedTypes(0, 'string');
        $resolver->setAllowedTypes(1, 'numeric');
        $resolver->setNormalizer(1, function (Options $options, $value) {
            $class = $options[0];
            if (null === $item = $this->doctrine->getRepository($class)->find($value)) {
                throw new InvalidArgumentException(sprintf('Could not find %s with id %d', $class, $value));
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

        return $this->exporter->cacheItem($item, [], true);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return self::NAME;
    }
}
