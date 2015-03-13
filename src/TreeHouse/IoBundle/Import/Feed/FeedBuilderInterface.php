<?php

namespace TreeHouse\IoBundle\Import\Feed;

use TreeHouse\Feeder\Feed;
use TreeHouse\Feeder\Modifier\Data\Transformer\TransformerInterface;
use TreeHouse\Feeder\Modifier\Item\ModifierInterface;
use TreeHouse\Feeder\Reader\ReaderInterface;
use TreeHouse\IoBundle\Import\Feed\Type\FeedTypeInterface;

interface FeedBuilderInterface
{
    /**
     * @param ModifierInterface $modifier
     * @param integer           $position Defaults to the next highest position
     * @param boolean           $continue Will be determined based on modifier type
     *
     * @throws \InvalidArgumentException If there already is a modifier at the given position
     */
    public function addModifier(ModifierInterface $modifier, $position = null, $continue = null);

    /**
     * @return ModifierInterface[]
     */
    public function getModifiers();

    /**
     * Shortcut for adding a field-value transformer
     *
     * @param TransformerInterface $transformer
     * @param string               $field
     * @param integer              $position
     * @param boolean              $continue
     */
    public function addTransformer(TransformerInterface $transformer, $field, $position = null, $continue = true);

    /**
     * @param integer $position
     *
     * @return boolean
     */
    public function hasModifierAt($position);

    /**
     * Removes existing modifier
     *
     * @param ModifierInterface $modifier
     */
    public function removeModifier(ModifierInterface $modifier);

    /**
     * Removes modifier at an existing position
     *
     * @param $position
     *
     * @throws \OutOfBoundsException If modifier does not exist
     */
    public function removeModifierAt($position);

    /**
     * @param FeedTypeInterface $type
     * @param ReaderInterface   $reader
     * @param array             $options
     *
     * @return Feed
     */
    public function build(FeedTypeInterface $type, ReaderInterface $reader, array $options = []);
}
