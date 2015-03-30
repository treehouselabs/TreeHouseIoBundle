<?php

namespace TreeHouse\IoBundle\Scrape;

use TreeHouse\IoBundle\Entity\Scraper as ScraperEntity;
use TreeHouse\IoBundle\Event\SourceEvent;
use TreeHouse\IoBundle\Model\SourceInterface;
use TreeHouse\IoBundle\Scrape\Crawler\RateLimit\EnablingRateLimitInterface;
use TreeHouse\IoBundle\Scrape\Exception\NotFoundException;
use TreeHouse\IoBundle\Source\SourceManagerInterface;

class SourceRevisitor
{
    /**
     * @var SourceManagerInterface
     */
    protected $sourceManager;

    /**
     * @var ScraperFactory
     */
    protected $factory;

    /**
     * Array of cached scrapers
     *
     * @var Scraper[]
     */
    protected $scrapers = [];

    /**
     * @param SourceManagerInterface $sourceManager
     * @param ScraperFactory         $factory
     */
    public function __construct(SourceManagerInterface $sourceManager, ScraperFactory $factory)
    {
        $this->sourceManager = $sourceManager;
        $this->factory       = $factory;
    }

    /**
     * Revisits a source. This basically means doing a scrape operation on the
     * source origin, only this time the source will be removed if the original
     * url was not found.
     *
     * @param SourceInterface $source
     * @param boolean         $async
     * @param boolean         $disableLimit
     */
    public function revisit(SourceInterface $source, $async = false, $disableLimit = false)
    {
        if (!$source->getOriginalUrl()) {
            throw new \InvalidArgumentException('Source does not contain an original url');
        }

        if ($async) {
            $this->revisitAfter($source, new \DateTime());

            return;
        }

        $scraper = $this->createScraper($source->getScraper(), $disableLimit);
        $scraper->setAsync($async);

        try {
            $scraper->scrape($source->getScraper(), $source->getOriginalUrl(), false);
        } catch (NotFoundException $e) {
            $this->removeSource($source);
        }
    }

    /**
     * Does a non-blocking revisit operation. Depending on the implementation,
     * this will mean adding a revisit job to some sort of queueing system.
     *
     * @param SourceInterface $source
     * @param \DateTime       $date
     */
    public function revisitAfter(SourceInterface $source, \DateTime $date)
    {
        $scraper = $this->createScraper($source->getScraper());
        $scraper->setAsync(true);
        $scraper->getEventDispatcher()->dispatch(
            ScraperEvents::SCRAPE_REVISIT_SOURCE,
            new SourceEvent($source),
            $date
        );
    }

    /**
     * @param SourceInterface $source
     */
    protected function removeSource(SourceInterface $source)
    {
        $this->sourceManager->remove($source);
        $this->sourceManager->flush($source);
    }

    /**
     * @param ScraperEntity $scraperEntity
     * @param boolean       $disableLimit
     *
     * @return ScraperInterface
     */
    protected function createScraper(ScraperEntity $scraperEntity, $disableLimit = false)
    {
        if (!array_key_exists($scraperEntity->getId(), $this->scrapers)) {
            $scraper = $this->factory->createScraper($scraperEntity);

            if ($disableLimit) {
                $limit = $scraper->getCrawler()->getRateLimit();
                if ($limit instanceof EnablingRateLimitInterface) {
                    $limit->disable();
                }
            }

            $this->scrapers[$scraperEntity->getId()] = $scraper;
        }

        return $this->scrapers[$scraperEntity->getId()];
    }
}
