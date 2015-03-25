<?php

namespace TreeHouse\IoBundle\Scrape\Parser;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use TreeHouse\Feeder\Modifier\Item\ModifierInterface;
use TreeHouse\IoBundle\Scrape\ScrapedItemBag;

interface ParserInterface
{
    /**
     * @return EventDispatcherInterface
     */
    public function getEventDispatcher();

    /**
     * @return ModifierInterface[]
     */
    public function getModifiers();

    /**
     * @param ModifierInterface $modifier
     * @param integer           $position
     * @param boolean           $continue
     *
     * @throws \InvalidArgumentException When position is invalid or duplicate
     */
    public function addModifier(ModifierInterface $modifier, $position = null, $continue = null);

    /**
     * @param ModifierInterface $modifier
     */
    public function removeModifier(ModifierInterface $modifier);

    /**
     * @param integer $position
     *
     * @throws \OutOfBoundsException When modifier at the position does not exist
     */
    public function removeModifierAt($position);

    /**
     * @param integer $position
     *
     * @return boolean
     */
    public function hasModifierAt($position);

    /**
     * @param ScrapedItemBag $item
     */
    public function parse(ScrapedItemBag $item);
}
