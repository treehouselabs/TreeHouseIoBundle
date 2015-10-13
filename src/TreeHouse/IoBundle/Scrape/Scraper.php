<?php

namespace TreeHouse\IoBundle\Scrape;

use GuzzleHttp\Psr7\Uri;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use TreeHouse\Feeder\Exception\FilterException;
use TreeHouse\Feeder\Exception\ModificationException;
use TreeHouse\Feeder\Exception\ValidationException;
use TreeHouse\IoBundle\Entity\Scraper as ScraperEntity;
use TreeHouse\IoBundle\Import\Exception\FailedItemException;
use TreeHouse\IoBundle\Scrape\Crawler\CrawlerInterface;
use TreeHouse\IoBundle\Scrape\Event\FailedItemEvent;
use TreeHouse\IoBundle\Scrape\Event\RateLimitEvent;
use TreeHouse\IoBundle\Scrape\Event\ScrapeResponseEvent;
use TreeHouse\IoBundle\Scrape\Event\ScrapeUrlEvent;
use TreeHouse\IoBundle\Scrape\Event\SkippedItemEvent;
use TreeHouse\IoBundle\Scrape\Event\SuccessItemEvent;
use TreeHouse\IoBundle\Scrape\Exception\CrawlException;
use TreeHouse\IoBundle\Scrape\Exception\RateLimitException;
use TreeHouse\IoBundle\Scrape\Exception\UnexpectedResponseException;
use TreeHouse\IoBundle\Scrape\Handler\HandlerInterface;
use TreeHouse\IoBundle\Scrape\Parser\ParserInterface;

class Scraper implements ScraperInterface
{
    /**
     * @var CrawlerInterface
     */
    protected $crawler;

    /**
     * @var ParserInterface
     */
    protected $parser;

    /**
     * @var HandlerInterface
     */
    protected $handler;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var boolean
     */
    protected $async = false;

    /**
     * @param CrawlerInterface         $crawler
     * @param ParserInterface          $parser
     * @param HandlerInterface         $handler
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(CrawlerInterface $crawler, ParserInterface $parser, HandlerInterface $handler, EventDispatcherInterface $dispatcher = null)
    {
        $this->crawler         = $crawler;
        $this->parser          = $parser;
        $this->handler         = $handler;
        $this->eventDispatcher = $dispatcher ?: new EventDispatcher();
    }

    /**
     * @inheritdoc
     */
    public function getCrawler()
    {
        return $this->crawler;
    }

    /**
     * @inheritdoc
     */
    public function getEventDispatcher()
    {
        return $this->eventDispatcher;
    }

    /**
     * @inheritdoc
     */
    public function setAsync($async)
    {
        $this->async = $async;
    }

    /**
     * @inheritdoc
     */
    public function isAsync()
    {
        return $this->async;
    }

    /**
     * @inheritdoc
     */
    public function scrape(ScraperEntity $scraper, $url, $continue = true)
    {
        $url = $this->normalizeUrl($url);

        try {
            $html = $this->crawler->crawl($url);

            // put it in a bag
            $item = new ScrapedItemBag($scraper, $url, $html);

            // scrape the item and the next urls
            $this->scrapeItem($item);

            if ($continue) {
                $this->scrapeNext($scraper);
            }
        } catch (RateLimitException $e) {
            $this->handleRateLimitException($scraper, $url, $e);

            throw $e;
        } catch (UnexpectedResponseException $e) {
            // we didn't get a 200 OK response, let the application know
            $this->eventDispatcher->dispatch(
                ScraperEvents::SCRAPE_URL_NOT_OK,
                new ScrapeResponseEvent($scraper, $url, $e->getResponse())
            );

            throw $e;
        } catch (CrawlException $e) {
            // something bad happened, let the calling command handle this
            throw $e;
        }
    }

    /**
     * @param ScraperEntity $scraper
     */
    public function scrapeNext(ScraperEntity $scraper)
    {
        foreach ($this->crawler->getNextUrls() as $url) {
            if ($this->async) {
                $this->scrapeAfter($scraper, $url, new \DateTime());
            } else {
                $this->scrape($scraper, $url);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function scrapeAfter(ScraperEntity $scraper, $url, \DateTime $date)
    {
        $this->eventDispatcher->dispatch(
            ScraperEvents::SCRAPE_NEXT_URL,
            new ScrapeUrlEvent($scraper, $url),
            $date
        );
    }

    /**
     * @param ScrapedItemBag $item
     */
    protected function scrapeItem(ScrapedItemBag $item)
    {
        try {
            $this->parser->parse($item);
            $source = $this->handler->handle($item);

            $this->eventDispatcher->dispatch(ScraperEvents::ITEM_SUCCESS, new SuccessItemEvent($this, $item, $source));
        } catch (FilterException $e) {
            $this->eventDispatcher->dispatch(ScraperEvents::ITEM_SKIPPED, new SkippedItemEvent($this, $item, $e->getMessage()));
        } catch (ValidationException $e) {
            $this->eventDispatcher->dispatch(ScraperEvents::ITEM_FAILED, new FailedItemEvent($this, $item, $e->getMessage()));
        } catch (FailedItemException $e) {
            $this->eventDispatcher->dispatch(ScraperEvents::ITEM_FAILED, new FailedItemEvent($this, $item, $e->getMessage()));
        } catch (ModificationException $e) {
            if ($e->getPrevious()) {
                $e = $e->getPrevious();
            }

            $this->eventDispatcher->dispatch(ScraperEvents::ITEM_FAILED, new FailedItemEvent($this, $item, $e->getMessage()));
        }
    }

    /**
     * @param ScraperEntity      $scraper
     * @param string             $url
     * @param RateLimitException $e
     */
    protected function handleRateLimitException(ScraperEntity $scraper, $url, RateLimitException $e)
    {
        $date = $e->getRetryDate();

        // dispatch event about rate limit
        if ($this->async) {
            $this->eventDispatcher->dispatch(
                ScraperEvents::RATE_LIMIT_REACHED,
                new RateLimitEvent($scraper, $url, $date)
            );
        } else {
            // if no retry-date is given, sleep for a minute
            $sleepTime = (null !== $date) ? $date->getTimestamp() - time() : 60;

            sleep($sleepTime);
        }
    }

    /**
     * @param string $url
     *
     * @return string
     */
    protected function normalizeUrl($url)
    {
        return (string) new Uri($url);
    }
}
