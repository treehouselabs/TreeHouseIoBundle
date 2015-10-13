<?php

namespace TreeHouse\IoBundle\Scrape\EventListener;

use GuzzleHttp\Psr7\Response;
use Symfony\Component\Console\Output\OutputInterface;
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

class ScrapeOutputSubscriber implements EventSubscriberInterface
{
    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @param OutputInterface $output
     */
    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
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
        $this->output->writeln(sprintf('<error>%s</error>', $event->getException()->getMessage()));
    }

    /**
     * @param SuccessItemEvent $event
     */
    public function onItemSuccess(SuccessItemEvent $event)
    {
        $this->output->writeln(
            sprintf('<info>✎  updated</info>: <comment>%s</comment>', (string) $event->getItem())
        );
    }

    /**
     * @param SkippedItemEvent $event
     */
    public function onItemSkipped(SkippedItemEvent $event)
    {
        $this->output->writeln(
            sprintf('<options=bold>#  skipped</>: <comment>%s</comment>', (string) $event->getItem())
        );

        $this->output->writeln(sprintf('<options=bold>    reason</>: %s', $event->getReason()));
    }

    /**
     * @param FailedItemEvent $event
     */
    public function onItemFailed(FailedItemEvent $event)
    {
        $this->output->writeln(
            sprintf('<fg=red;options=bold>✘   failed</>: <comment>%s</comment>', (string) $event->getItem())
        );

        $this->output->writeln(sprintf('<fg=red;options=bold>    reason</>: %s', $event->getReason()));
    }

    public function onScrapeNextUrl(ScrapeUrlEvent $event)
    {
        $this->output->writeln(
            sprintf('<options=bold>⇒  next url</>: <comment>%s</comment>', (string) $event->getUrl())
        );
    }

    /**
     * @param RateLimitEvent $event
     */
    public function onRateLimitReached(RateLimitEvent $event)
    {
        $seconds = $event->getRetryDate()->getTimestamp() - time();
        $this->output->writeln(sprintf('Rate limit reached, try again after <info>%d</info> seconds', $seconds));
    }

    /**
     * @param ScrapeResponseEvent $event
     */
    public function onScrapeUrlNotOk(ScrapeResponseEvent $event)
    {
        $response = $event->getResponse();

        $code = $response->getStatusCode();
        $text = (new Response($code))->getReasonPhrase();

        $this->output->writeln(sprintf('Server replied with response <info>%d (%s)</info>', $code, $text));
    }
}
