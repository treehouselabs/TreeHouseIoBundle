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
     * @param int               $position
     * @param bool              $continue
     *
     * @throws \InvalidArgumentException When position is invalid or duplicate
     */
    public function addModifier(ModifierInterface $modifier, $position = null, $continue = null);

    /**
     * @param ModifierInterface $modifier
     */
    public function removeModifier(ModifierInterface $modifier);

    /**
     * @param int $position
     *
     * @throws \OutOfBoundsException When modifier at the position does not exist
     */
    public function removeModifierAt($position);

    /**
     * @param int $position
     *
     * @return bool
     */
    public function hasModifierAt($position);

    /**
     * @param ScrapedItemBag $item
     */
    public function parse(ScrapedItemBag $item);
}
