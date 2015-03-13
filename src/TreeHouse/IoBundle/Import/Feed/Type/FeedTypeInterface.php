<?php

namespace TreeHouse\IoBundle\Import\Feed\Type;

use Symfony\Component\OptionsResolver\OptionsResolver;
use TreeHouse\IoBundle\Import\Feed\FeedBuilderInterface;

interface FeedTypeInterface
{
    /**
     * @param OptionsResolver $resolver
     */
    public function setOptions(OptionsResolver $resolver);

    /**
     * @param FeedBuilderInterface $builder
     * @param array                $options
     */
    public function build(FeedBuilderInterface $builder, array $options);

    /**
     * Returns item name from the feed. This is used by a
     * reader to locate and use item nodes.
     *
     * For instance, if an XML feed looks like this...
     *
     * <code>
     *   <feed>
     *     <object>
     *      [...]
     *     </object>
     *   </feed>
     * </code>
     *
     * ...set this method to return 'object', and you're good to go.
     *
     * @return string
     */
    public function getItemName();

    /**
     * Returns a callable to obtain the original id.
     * The function is passed the item (ParameterBag) as the only
     * argument, and must return the id value as a string.
     *
     * @return \Closure
     */
    public function getOriginalIdCallback();

    /**
     * Returns a callable to obtain the original url.
     * The function is passed the item (ParameterBag) as the only
     * argument, and must return the url value as a string, or
     * null when not available.
     *
     * @return \Closure
     */
    public function getOriginalUrlCallback();

    /**
     * Returns a callable to obtain the modification date.
     * The function is passed the item (ParameterBag) as the only
     * argument, and must return the date as a DateTime object, or
     * null when not available.
     *
     * @return \Closure
     */
    public function getModificationDateCallback();
}
