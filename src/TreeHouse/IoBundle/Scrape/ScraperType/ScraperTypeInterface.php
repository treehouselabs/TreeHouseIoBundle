<?php

namespace FM\IoBundle\Scrape\ScraperType;

use FM\IoBundle\Scrape\ScraperBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

interface ScraperTypeInterface
{
    /**
     * @param ScraperBuilderInterface $builder
     * @param array                   $options
     */
    public function build(ScraperBuilderInterface $builder, array $options = []);

    /**
     * @param OptionsResolver $options
     */
    public function setOptions(OptionsResolver $options);

    /**
     * Is this scraper type supporting the given url?
     *
     * @param string $url
     *
     * @return bool
     */
    public function supports($url);
}
