<?php

namespace FM\IoBundle\Scrape\Modifier\Item\Mapper;

use FM\IoBundle\Entity\Scraper;
use FM\IoBundle\Scrape\Model\ScrapedItemBag;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\ParameterBag;

class ScrapedItemBagMapper implements NodeMapperInterface
{
    /**
     * @var Scraper
     */
    protected $scraper;

    /**
     * @var Crawler
     */
    protected $crawler;

    /**
     * @var callable
     */
    protected $originalIdCallback;

    /**
     * @var callable
     */
    protected $modificationDateCallback;

    /**
     * @param Scraper  $scraper
     * @param string   $originalUrl
     * @param callable $originalIdCallback
     * @param callable $modificationDateCallback
     */
    public function __construct(Scraper $scraper, $originalUrl, $originalIdCallback, $modificationDateCallback)
    {
        if (!is_callable($originalIdCallback)) {
            throw new \InvalidArgumentException('$originalIdCallback must be a callable');
        }

        if (!is_callable($modificationDateCallback)) {
            throw new \InvalidArgumentException('$modificationDateCallback must be a callable');
        }

        $this->scraper                  = $scraper;
        $this->originalUrl              = $originalUrl;
        $this->originalIdCallback       = $originalIdCallback;
        $this->modificationDateCallback = $modificationDateCallback;
    }

    /**
     * @inheritdoc
     */
    public function setCrawler(Crawler $crawler)
    {
        $this->crawler = $crawler;
    }

    /**
     * @inheritdoc
     */
    public function map(ParameterBag $item)
    {
        $bag = new ScrapedItemBag($this->scraper, $this->originalUrl, $item->all());

        if ($id = call_user_func($this->originalIdCallback, $this->crawler)) {
            $bag->setOriginalId($id);
        }

        if ($date = call_user_func($this->modificationDateCallback, $this->crawler)) {
            $bag->setDatetimeModified($date);
        }

        return $bag;
    }
}
