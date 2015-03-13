<?php

namespace TreeHouse\IoBundle\Import\Importer\Type;

use Symfony\Component\OptionsResolver\OptionsResolver;
use TreeHouse\IoBundle\Entity\Import;
use TreeHouse\IoBundle\Import\Importer\ImporterBuilderInterface;

class DefaultImporterType implements ImporterTypeInterface
{
    /**
     * @inheritdoc
     */
    public function setOptions(OptionsResolver $resolver)
    {
    }

    /**
     * @inheritdoc
     */
    public function build(ImporterBuilderInterface $builder, Import $import, array $options)
    {
    }
}
