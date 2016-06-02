<?php

namespace TreeHouse\IoBundle\Bridge\WorkerBundle\Executor;

use Doctrine\Common\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidArgumentException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use TreeHouse\IoBundle\Entity\Scraper as ScraperEntity;
use TreeHouse\IoBundle\Scrape\Exception\CrawlException;
use TreeHouse\IoBundle\Scrape\Exception\RateLimitException;
use TreeHouse\IoBundle\Scrape\ScraperFactory;
use TreeHouse\WorkerBundle\Exception\RescheduleException;
use TreeHouse\WorkerBundle\Executor\AbstractExecutor;

class ScrapeUrlExecutor extends AbstractExecutor
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
        $this->factory = $factory;
        $this->logger = $logger;
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
    public function configurePayload(OptionsResolver $resolver)
    {
        $resolver->setRequired(0);
        $resolver->setAllowedTypes(0, 'numeric');
        $resolver->setNormalizer(0, function (Options $options, $value) {
            if (null === $scraper = $this->findScraper($value)) {
                throw new InvalidArgumentException(sprintf('Could not find scraper with id %d', $value));
            }

            return $scraper;
        });

        $resolver->setRequired(1);
        $resolver->setAllowedTypes(1, 'string');
    }

    /**
     * @inheritdoc
     */
    public function execute(array $payload)
    {
        /** @var ScraperEntity $entity */
        /** @var string $url */
        list($entity, $url) = $payload;

        $scraper = $this->factory->createScraper($entity);
        $scraper->setAsync(true);

        try {
            $scraper->scrape($entity, $url);

            return true;
        } catch (RateLimitException $e) {
            $re = new RescheduleException();

            if ($date = $e->getRetryDate()) {
                $re->setRescheduleDate($date);
            }

            throw $re;
        } catch (CrawlException $e) {
            $this->logger->error($e->getMessage(), ['url' => $e->getUrl()]);

            return false;
        }
    }

    /**
     * @param int $scraperId
     *
     * @return ScraperEntity
     */
    protected function findScraper($scraperId)
    {
        return $this->doctrine->getRepository('TreeHouseIoBundle:Scraper')->find($scraperId);
    }
}
