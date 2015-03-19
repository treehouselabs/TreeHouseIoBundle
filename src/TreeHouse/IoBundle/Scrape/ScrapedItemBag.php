<?php

namespace TreeHouse\IoBundle\Scrape;

use TreeHouse\IoBundle\Entity\Scraper as ScraperEntity;
use TreeHouse\IoBundle\Item\ItemBag;

class ScrapedItemBag extends ItemBag
{
    /**
     * @var ScraperEntity
     */
    protected $scraper;

    /**
     * @param ScraperEntity $scraper
     * @param string        $originalUrl
     * @param string        $originalData
     */
    public function __construct(ScraperEntity $scraper, $originalUrl, $originalData)
    {
        parent::__construct([]);

        $this->scraper      = $scraper;
        $this->originalUrl  = $originalUrl;
        $this->originalData = $originalData;

        $this->setOriginalId(md5($originalUrl));
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return sprintf('%s:%s', $this->scraper->getOrigin()->getName(), $this->originalUrl);
    }

    /**
     * @return ScraperEntity
     */
    public function getScraper()
    {
        return $this->scraper;
    }

    /**
     * @return string
     */
    public function getOriginalData()
    {
        return $this->originalData;
    }
}
