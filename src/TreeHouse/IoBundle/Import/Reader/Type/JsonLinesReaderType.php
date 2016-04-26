<?php

namespace TreeHouse\IoBundle\Import\Reader\Type;

use Symfony\Component\OptionsResolver\OptionsResolver;
use TreeHouse\IoBundle\Import\Reader\ReaderBuilderInterface;

class JsonLinesReaderType implements ReaderTypeInterface
{
    /**
     * @param ReaderBuilderInterface $builder
     * @param array $options
     *
     * @return void
     */
    public function build(ReaderBuilderInterface $builder, array $options)
    {
        $builder->setReaderType(ReaderBuilderInterface::READER_TYPE_JSONLINES);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function setOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired([
            'forced',
            'partial',
        ]);
    }
}
