<?php

namespace FM\IoBundle\Scrape;

use FM\Feeder\Exception\FilterException;
use FM\Feeder\Exception\ValidationException;
use FM\Feeder\Modifier\Item\ModifierInterface;
use FM\IoBundle\Scrape\Model\ScrapedItemBag;

interface ScraperInterface
{
    /**
     * @param ModifierInterface $modifier
     * @param boolean           $continue Will be determined based on modifier type
     */
    public function addModifier(ModifierInterface $modifier, $continue = null);

    public function run(Scraper $scraper);

    /**
     * @param string $html
     * @param string $url
     *
     * @throws FilterException
     * @throws ValidationException
     *
     * @return ScrapedItemBag
     */
    public function scrape($html, $url);
}
