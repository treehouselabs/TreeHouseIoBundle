<?php

namespace TreeHouse\IoBundle\Import\Reader\Type;

use Symfony\Component\OptionsResolver\OptionsResolver;
use TreeHouse\Feeder\Resource\Transformer\RemoveByteOrderMarkTransformer;
use TreeHouse\Feeder\Resource\Transformer\RemoveControlCharactersTransformer;
use TreeHouse\IoBundle\Import\Reader\ReaderBuilderInterface;

class XmlReaderType implements ReaderTypeInterface
{
    /**
     * @inheritdoc
     */
    public function setOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired([
            'forced',
            'partial',
            'remove_control_characters',
            'remove_byte_order_marks',
        ]);

        $resolver->setAllowedTypes('forced', 'bool');
        $resolver->setAllowedTypes('partial', 'bool');
        $resolver->setAllowedTypes('remove_control_characters', 'bool');
        $resolver->setAllowedTypes('remove_byte_order_marks', 'bool');

        $resolver->setDefaults([
            'remove_control_characters' => true,
            'remove_byte_order_marks'   => true,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function build(ReaderBuilderInterface $builder, array $options)
    {
        if ($options['remove_control_characters']) {
            // remove control characters from parts
            $builder->addPartResourceTransformer(new RemoveControlCharactersTransformer());
        }

        if ($options['remove_byte_order_marks']) {
            // remove BOM's from parts
            $builder->addPartResourceTransformer(new RemoveByteOrderMarkTransformer());
        }
    }
}
