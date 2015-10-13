<?php

namespace TreeHouse\IoBundle\Scrape\EventListener;

use GuzzleHttp\Psr7\Response;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use TreeHouse\Feeder\Event\FailedItemModificationEvent;
use TreeHouse\Feeder\FeedEvents;
use TreeHouse\IoBundle\Scrape\Event\FailedItemEvent;
use TreeHouse\IoBundle\Scrape\Event\RateLimitEvent;
use TreeHouse\IoBundle\Scrape\Event\ScrapeResponseEvent;
use TreeHouse\IoBundle\Scrape\Event\ScrapeUrlEvent;
use TreeHouse\IoBundle\Scrape\Event\SkippedItemEvent;
use TreeHouse\IoBundle\Scrape\Event\SuccessItemEvent;
use TreeHouse\IoBundle\Scrape\ScraperEvents;

class ScrapeLoggingSubscriber implements EventSubscriberInterface
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents()
    {
        return [
            FeedEvents::ITEM_MODIFICATION_FAILED => 'onItemModificationFailure',
            ScraperEvents::ITEM_SUCCESS          => 'onItemSuccess',
            ScraperEvents::ITEM_FAILED           => 'onItemFailed',
            ScraperEvents::ITEM_SKIPPED          => 'onItemSkipped',
            ScraperEvents::SCRAPE_NEXT_URL       => 'onScrapeNextUrl',
            ScraperEvents::RATE_LIMIT_REACHED    => 'onRateLimitReached',
            ScraperEvents::SCRAPE_URL_NOT_OK     => 'onScrapeUrlNotOk',
        ];
    }

    /**
     * @param FailedItemModificationEvent $event
     */
    public function onItemModificationFailure(FailedItemModificationEvent $event)
    {
        $this->logger->warning($event->getException()->getMessage());
    }

    /**
     * @param SuccessItemEvent $event
     */
    public function onItemSuccess(SuccessItemEvent $event)
    {
        $this->logger->info(sprintf('✎  updated: %s', (string) $event->getItem()));
    }

    /**
     * @param SkippedItemEvent $event
     */
    public function onItemSkipped(SkippedItemEvent $event)
    {
        $this->logger->info(sprintf('#  skipped: %s', (string) $event->getItem()));
        $this->logger->debug(sprintf('    reason: %s', $event->getReason()));
    }

    /**
     * @param FailedItemEvent $event
     */
    public function onItemFailed(FailedItemEvent $event)
    {
        $this->logger->warning(sprintf('✘   failed: %s', (string) $event->getItem()));
        $this->logger->debug(sprintf('    reason: %s', $event->getReason()));
    }

    /**
     * @param ScrapeUrlEvent $event
     */
    public function onScrapeNextUrl(ScrapeUrlEvent $event)
    {
        $this->logger->debug(sprintf('⇒  next url: %s', (string) $event->getUrl()));
    }

    /**
     * @param RateLimitEvent $event
     */
    public function onRateLimitReached(RateLimitEvent $event)
    {
        $seconds = $event->getRetryDate()->getTimestamp() - time();
        $this->logger->debug(sprintf('Rate limit reached, try again after %d seconds', $seconds));
    }

    /**
     * @param ScrapeResponseEvent $event
     */
    public function onScrapeUrlNotOk(ScrapeResponseEvent $event)
    {
        $response = $event->getResponse();

        $code = $response->getStatusCode();
        $text = (new Response($code))->getReasonPhrase();

        $this->logger->debug(sprintf('Server replied with response %d (%s)', $code, $text));
    }
}
