<?php

namespace TreeHouse\IoBundle\Import\Feed;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use TreeHouse\Feeder\Feed;
use TreeHouse\Feeder\Modifier\Data\Transformer\TransformerInterface;
use TreeHouse\Feeder\Modifier\Item\Filter\FilterInterface;
use TreeHouse\Feeder\Modifier\Item\ModifierInterface;
use TreeHouse\Feeder\Modifier\Item\Transformer\DataTransformer;
use TreeHouse\Feeder\Modifier\Item\Validator\ValidatorInterface;
use TreeHouse\Feeder\Reader\ReaderInterface;
use TreeHouse\Feeder\Reader\XmlReader;
use TreeHouse\IoBundle\Import\Feed\Type\FeedTypeInterface;

class FeedBuilder implements FeedBuilderInterface
{
    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var array<ModifierInterface, boolean>
     */
    protected $modifiers = [];

    /**
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @inheritdoc
     */
    public function addModifier(ModifierInterface $modifier, $position = null, $continue = null)
    {
        if (null === $position) {
            $position = sizeof($this->modifiers) ? (max(array_keys($this->modifiers)) + 1) : 0;
        }

        if (null === $continue) {
            $continue = (!$modifier instanceof FilterInterface) && (!$modifier instanceof ValidatorInterface);
        }

        if (array_key_exists($position, $this->modifiers)) {
            throw new \InvalidArgumentException(sprintf('There already is a modifier at position %d', $position));
        }

        $this->modifiers[$position] = [$modifier, $continue];
    }

    /**
     * @inheritdoc
     */
    public function getModifiers()
    {
        return $this->modifiers;
    }

    /**
     * @inheritdoc
     */
    public function addTransformer(TransformerInterface $transformer, $field, $position = null, $continue = true)
    {
        $this->addModifier(new DataTransformer($transformer, $field), $position, $continue);
    }

    /**
     * @inheritdoc
     */
    public function hasModifierAt($position)
    {
        return array_key_exists($position, $this->modifiers);
    }

    /**
     * @inheritdoc
     */
    public function removeModifier(ModifierInterface $modifier)
    {
        foreach ($this->modifiers as $position => list($mod)) {
            if ($mod === $modifier) {
                unset($this->modifiers[$position]);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function removeModifierAt($position)
    {
        if (!array_key_exists($position, $this->modifiers)) {
            throw new \OutOfBoundsException(sprintf('There is no modifier at position %d', $position));
        }

        unset($this->modifiers[$position]);
    }

    /**
     * @inheritdoc
     */
    public function build(FeedTypeInterface $type, ReaderInterface $reader, array $options = [])
    {
        $resolver = $this->getOptionsResolver($type);
        $type->build($this, $resolver->resolve($options));

        if (!$itemName = $type->getItemName()) {
            throw new \LogicException(
                sprintf('"%s::getItemName()" must return a valid item name', get_class($type))
            );
        }

        return $this->createFeed($reader, $this->eventDispatcher, $itemName);
    }

    /**
     * @param ReaderInterface          $reader
     * @param EventDispatcherInterface $dispatcher
     * @param string                   $itemName
     *
     * @return Feed
     */
    protected function createFeed(ReaderInterface $reader, EventDispatcherInterface $dispatcher, $itemName)
    {
        if ($reader instanceof XmlReader) {
            $reader->setNodeCallback($itemName);
        }

        $feed = new Feed($reader, $dispatcher);

        /** @var ModifierInterface $modifier */
        foreach ($this->modifiers as $position => list($modifier, $continue)) {
            $feed->addModifier($modifier, $position, $continue);
        }

        return $feed;
    }

    /**
     * @param FeedTypeInterface $type
     *
     * @return OptionsResolver
     */
    protected function getOptionsResolver(FeedTypeInterface $type)
    {
        $resolver = new OptionsResolver();
        $type->setOptions($resolver);

        return $resolver;
    }
}
