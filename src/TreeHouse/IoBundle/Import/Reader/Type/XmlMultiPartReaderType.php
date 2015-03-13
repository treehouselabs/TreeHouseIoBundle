<?php

namespace TreeHouse\IoBundle\Import\Reader\Type;

use Symfony\Component\OptionsResolver\OptionsResolver;
use TreeHouse\Feeder\Reader\XmlReader;
use TreeHouse\Feeder\Resource\Transformer\MultiPartTransformer;
use TreeHouse\Feeder\Writer\XmlWriter;
use TreeHouse\IoBundle\Import\Reader\ReaderBuilderInterface;

class XmlMultiPartReaderType extends XmlReaderType
{
    /**
     * @var integer
     */
    protected $defaultPartSize = 1000;

    /**
     * @return integer
     */
    public function getDefaultPartSize()
    {
        return $this->defaultPartSize;
    }

    /**
     * @param integer $defaultPartSize
     *
     * @return $this
     */
    public function setDefaultPartSize($defaultPartSize)
    {
        $this->defaultPartSize = $defaultPartSize;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setOptions(OptionsResolver $resolver)
    {
        parent::setOptions($resolver);

        $resolver->setRequired([
            'part_size',
            'node_name',
        ]);

        $resolver->setAllowedTypes('part_size', 'integer');
        $resolver->setAllowedTypes('node_name', 'string');

        $resolver->setDefaults([
            'part_size' => $this->defaultPartSize,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function build(ReaderBuilderInterface $builder, array $options)
    {
        parent::build($builder, $options);

        $reader = new XmlReader([]);
        $reader->setNodeCallback($options['node_name']);

        // break into parts
        $builder->addResourceTransformer(new MultiPartTransformer($reader, new XmlWriter(), $options['part_size']));
    }
}
