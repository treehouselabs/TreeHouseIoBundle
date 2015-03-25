<?php

namespace TreeHouse\IoBundle\Scrape\Parser;

use TreeHouse\Feeder\Modifier\Data\Transformer\TransformerInterface;
use TreeHouse\Feeder\Modifier\Item\ModifierInterface;
use TreeHouse\IoBundle\Scrape\Parser\Type\ParserTypeInterface;

interface ParserBuilderInterface
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
     * Adds the given modifier between the start and end index, if there is a vacant position
     *
     * @param ModifierInterface $modifier
     * @param integer           $startIndex
     * @param integer           $endIndex
     * @param boolean           $continue
     *
     * @throws \OutOfBoundsException
     */
    public function addModifierBetween(ModifierInterface $modifier, $startIndex, $endIndex, $continue = null);

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
     * Adds the given transformer between the start and end index, if there is a vacant position
     *
     * @param TransformerInterface $transformer
     * @param string               $field
     * @param integer              $startIndex
     * @param integer              $endIndex
     * @param boolean              $continue
     *
     * @throws \OutOfBoundsException
     */
    public function addTransformerBetween(TransformerInterface $transformer, $field, $startIndex, $endIndex, $continue = null);

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
     * Builds the parser
     *
     * @param ParserTypeInterface $type
     * @param array               $options
     *
     * @return ParserInterface
     */
    public function build(ParserTypeInterface $type, array $options);
}
