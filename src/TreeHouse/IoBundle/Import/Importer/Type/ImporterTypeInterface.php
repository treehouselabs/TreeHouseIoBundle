<?php

namespace TreeHouse\IoBundle\Import\Importer\Type;

use Symfony\Component\OptionsResolver\OptionsResolver;
use TreeHouse\IoBundle\Entity\Import;
use TreeHouse\IoBundle\Import\Importer\ImporterBuilderInterface;

interface ImporterTypeInterface
{
    /**
     * @param OptionsResolver $resolver
     */
    public function setOptions(OptionsResolver $resolver);

    /**
     * @param ImporterBuilderInterface $builder
     * @param Import                   $import
     * @param array                    $options
     */
    public function build(ImporterBuilderInterface $builder, Import $import, array $options);
}
