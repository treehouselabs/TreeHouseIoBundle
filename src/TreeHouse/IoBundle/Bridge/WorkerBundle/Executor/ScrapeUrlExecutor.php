<?php

namespace TreeHouse\IoBundle\Bridge\WorkerBundle\Executor;

use Doctrine\Common\Persistence\ManagerRegistry;
use FM\WorkerBundle\Monolog\LoggerAggregate;
use FM\WorkerBundle\Queue\JobExecutor;
use Psr\Log\LoggerInterface;
use TreeHouse\IoBundle\Entity\Scraper as ScraperEntity;
use TreeHouse\IoBundle\Scrape\EventListener\ScrapeLoggingSubscriber;
use TreeHouse\IoBundle\Scrape\Exception\CrawlException;
use TreeHouse\IoBundle\Scrape\ScraperFactory;

class ScrapeUrlExecutor extends JobExecutor implements LoggerAggregate
{
    const NAME = 'scrape.url';

    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    /**
     * @var ScraperFactory
     */
    protected $factory;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param ManagerRegistry $doctrine
     * @param ScraperFactory  $factory
     * @param LoggerInterface $logger
     */
    public function __construct(ManagerRegistry $doctrine, ScraperFactory $factory, LoggerInterface $logger)
    {
        $this->doctrine = $doctrine;
        $this->factory  = $factory;
        $this->logger   = $logger;

        $this->factory->getEventDispatcher()->addSubscriber(new ScrapeLoggingSubscriber($this->logger));
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @inheritdoc
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @inheritdoc
     */
    public function execute(array $payload)
    {
        if (sizeof($payload) !== 2) {
            throw new \InvalidArgumentException('Payload must contain a scraper id and a url');
        }

        list($scraperId, $url) = $payload;

        $entity  = $this->findScraper($scraperId);

        $scraper = $this->factory->createScraper($entity);
        $scraper->setAsync(true);

        try {
            return $scraper->scrape($entity, $url);
        } catch (CrawlException $e) {
            $this->logger->error($e->getMessage(), ['url' => $e->getUrl()]);

            return false;
        }
    }

    /**
     * @param integer $scraperId
     *
     * @return ScraperEntity
     */
    protected function findScraper($scraperId)
    {
        return $this->doctrine->getRepository('TreeHouseIoBundle:Scraper')->find($scraperId);
    }
}
