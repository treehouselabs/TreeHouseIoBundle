<?php

namespace TreeHouse\IoBundle\Import\Importer;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use TreeHouse\IoBundle\Entity\Import;
use TreeHouse\IoBundle\Import\Handler\HandlerInterface;
use TreeHouse\IoBundle\Import\Importer\Type\ImporterTypeInterface;

class ImporterBuilder implements ImporterBuilderInterface
{
    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->eventDispatcher = $dispatcher;
    }

    /**
     * @return EventDispatcherInterface
     */
    public function getEventDispatcher()
    {
        return $this->eventDispatcher;
    }

    /**
     * @inheritdoc
     */
    public function build(ImporterTypeInterface $type, Import $import, HandlerInterface $handler, array $options)
    {
        $resolver = $this->getOptionsResolver($type);

        $type->build($this, $import, $resolver->resolve($options));

        return new Importer($import, $handler, $this->eventDispatcher);
    }

    /**
     * @param ImporterTypeInterface $type
     *
     * @return OptionsResolver
     */
    protected function getOptionsResolver(ImporterTypeInterface $type)
    {
        $resolver = new OptionsResolver();
        $type->setOptions($resolver);

        return $resolver;
    }
}
