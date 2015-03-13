<?php

namespace FM\IoBundle\Scrape;

use FM\CargoBundle\Origin\OriginManager;
use FM\IoBundle\Model\SourceInterface;
use FM\IoBundle\Source\SourceManagerInterface;
use Guzzle\Http\Url;

class ScraperImporter
{
    /**
     * @var ScraperBuilderInterface
     */
    protected $scraperBuilder;

    /**
     * @var OriginManager
     */
    protected $originManager;

    /**
     * @var ScraperFeedManager
     */
    protected $feedManager;

    /**
     * @var SourceManagerInterface
     */
    protected $sourceManager;

    /**
     * @param ScraperBuilderInterface $scraperBuilder
     */
    public function setScraperBuilder(ScraperBuilderInterface $scraperBuilder)
    {
        $this->scraperBuilder = $scraperBuilder;
    }

    /**
     * @param OriginManager $originManager
     */
    public function setOriginManager(OriginManager $originManager)
    {
        $this->originManager = $originManager;
    }

    /**
     * @param ScraperFeedManager $feedManager
     */
    public function setFeedManager(ScraperFeedManager $feedManager)
    {
        $this->feedManager = $feedManager;
    }

    /**
     * @param SourceManagerInterface $sourceManager
     */
    public function setSourceManager(SourceManagerInterface $sourceManager)
    {
        $this->sourceManager = $sourceManager;
    }

    /**
     * @param string  $html
     * @param string  $url
     * @param integer $originalId
     * @param integer $originId
     *
     * @return SourceInterface
     */
    public function import($html, $url, $originalId, $originId)
    {
        $url = Url::factory($url);

        $scraper = $this->scraperBuilder->build($url);

        // scraper
        $scraperDataBag = $scraper->scrape($html, (string) $url);

        // get origin
        $origin = $this->originManager->findById($originId);

        // get/create the scraper feed
        $feed = $this->feedManager->findFeedOrCreate();
        $feed->setOrigin($origin);

        // get a source
        $source = $this->sourceManager->findSourceOrCreate($feed, $originalId, (string) $url);
        $source->setData($scraperDataBag->all());
        $source->setDatetimeLastVisited(new \DateTime());
        $source->setDatetimeModified(new \DateTime());

        // persist source and flush all (e.g.: source, feed, origin)
        $this->sourceManager->persist($source);
        $this->sourceManager->flush();

        return $source;
    }
}
