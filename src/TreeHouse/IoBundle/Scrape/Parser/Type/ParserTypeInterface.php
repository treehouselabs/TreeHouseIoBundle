<?php

namespace TreeHouse\IoBundle\Scrape\Parser\Type;

use Symfony\Component\OptionsResolver\OptionsResolver;
use TreeHouse\IoBundle\Scrape\Parser\ParserBuilderInterface;

interface ParserTypeInterface
{
    /**
     * @param ParserBuilderInterface $parser
     * @param array                  $options
     */
    public function build(ParserBuilderInterface $parser, array $options);

    /**
     * @param OptionsResolver $resolver
     */
    public function setOptions(OptionsResolver $resolver);
}
