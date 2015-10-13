<?php

namespace TreeHouse\IoBundle\Import\Reader;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use TreeHouse\Feeder\Reader\XmlReader;
use TreeHouse\Feeder\Resource\FileResource;
use TreeHouse\Feeder\Resource\ResourceCollection;
use TreeHouse\Feeder\Resource\Transformer\ResourceTransformerInterface;
use TreeHouse\Feeder\Transport\TransportInterface;
use TreeHouse\IoBundle\Import\Feed\TransportFactory;
use TreeHouse\IoBundle\Import\Reader\Type\ReaderTypeInterface;

class ReaderBuilder implements ReaderBuilderInterface
{
    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var string
     */
    protected $destinationDir;

    /**
     * @var string
     */
    protected $readerType;

    /**
     * @var array<string, ResourceTransformer[]>
     */
    protected $transformers = [];

    /**
     * @param EventDispatcherInterface $dispatcher
     * @param string                   $destinationDir
     */
    public function __construct(EventDispatcherInterface $dispatcher, $destinationDir)
    {
        $this->eventDispatcher = $dispatcher;
        $this->destinationDir = $destinationDir;
    }

    /**
     * @inheritdoc
     */
    public function build(ReaderTypeInterface $type, array $transportConfig, $resourceType, array $options)
    {
        $resolver = $this->getOptionsResolver($type);

        $transport = $this->createTransport($transportConfig, $this->destinationDir, $this->eventDispatcher);
        $resources = new ResourceCollection([new FileResource($transport)]);

        $type->build($this, $resolver->resolve($options));

        if (isset($this->transformers[$resourceType])) {
            foreach ($this->transformers[$resourceType] as $transformer) {
                $resources->addTransformer($transformer);
            }
        }

        switch ($this->readerType) {
            case self::READER_TYPE_XML:
                // xml is the default, let it fall through
            default:
                return new XmlReader($resources, $this->eventDispatcher);
        }
    }

    /**
     * @inheritdoc
     */
    public function addResourceTransformer(ResourceTransformerInterface $transformer)
    {
        $this->transformers[self::RESOURCE_TYPE_MAIN][] = $transformer;
    }

    /**
     * @inheritdoc
     */
    public function addPartResourceTransformer(ResourceTransformerInterface $transformer)
    {
        $this->transformers[self::RESOURCE_TYPE_PART][] = $transformer;
    }

    /**
     * @inheritdoc
     */
    public function setReaderType($type)
    {
        $this->readerType = $type;
    }

    /**
     * @param ReaderTypeInterface $type
     *
     * @return OptionsResolver
     */
    protected function getOptionsResolver(ReaderTypeInterface $type)
    {
        $resolver = new OptionsResolver();
        $type->setOptions($resolver);

        return $resolver;
    }

    /**
     * @param array                    $transportConfig
     * @param string                   $destinationDir
     * @param EventDispatcherInterface $dispatcher
     *
     * @return TransportInterface
     */
    protected function createTransport(array $transportConfig, $destinationDir, EventDispatcherInterface $dispatcher)
    {
        $transport = TransportFactory::createTransportFromConfig($transportConfig);
        $transport->setDestinationDir($destinationDir);
        $transport->setEventDispatcher($dispatcher);

        return $transport;
    }
}
