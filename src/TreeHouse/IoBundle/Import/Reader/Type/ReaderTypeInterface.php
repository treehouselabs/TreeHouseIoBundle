<?php

namespace TreeHouse\IoBundle\Import\Reader\Type;

use Symfony\Component\OptionsResolver\OptionsResolver;
use TreeHouse\IoBundle\Import\Reader\ReaderBuilderInterface;

interface ReaderTypeInterface
{
    /**
     * @param OptionsResolver $resolver
     */
    public function setOptions(OptionsResolver $resolver);

    /**
     * @param ReaderBuilderInterface $builder
     * @param array                  $options
     */
    public function build(ReaderBuilderInterface $builder, array $options);
}
