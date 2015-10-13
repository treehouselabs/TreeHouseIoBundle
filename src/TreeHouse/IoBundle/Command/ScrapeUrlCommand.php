<?php

namespace TreeHouse\IoBundle\Command;

use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TreeHouse\IoBundle\Entity\Scraper;
use TreeHouse\IoBundle\Entity\Scraper as ScraperEntity;
use TreeHouse\IoBundle\Scrape\Crawler\RateLimit\EnablingRateLimitInterface;
use TreeHouse\IoBundle\Scrape\EventListener\ScrapeOutputSubscriber;
use TreeHouse\IoBundle\Scrape\Exception\CrawlException;
use TreeHouse\IoBundle\Scrape\ScraperFactory;

class ScrapeUrlCommand extends Command
{
    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    /**
     * @var ScraperFactory
     */
    protected $factory;

    /**
     * @param ManagerRegistry $doctrine
     * @param ScraperFactory  $factory
     */
    public function __construct(ManagerRegistry $doctrine, ScraperFactory $factory)
    {
        $this->doctrine = $doctrine;
        $this->factory = $factory;

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('io:scrape:url');
        $this->setDescription('Schedules imports for one or more feeds');
        $this->addArgument('scraper', InputArgument::REQUIRED, 'The scraper id');
        $this->addArgument('url', InputArgument::REQUIRED, 'The url to scrape');
        $this->addOption(
            'async',
            'a',
            InputOption::VALUE_NONE,
            'Whether to scrape asynchronous. Doing so will queue next pages, rather them processing them right away'
        );
        $this->addOption('no-limit', null, InputOption::VALUE_NONE, 'Disables the rate limit');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (null === $entity = $this->findScraper($input->getArgument('scraper'))) {
            throw new \RuntimeException(sprintf('Scraper %d not found', $input->getArgument('scraper')));
        }

        $scraper = $this->factory->createScraper($entity);

        if ($input->getOption('async')) {
            $scraper->setAsync(true);
        }

        if ($input->getOption('no-limit')) {
            $limit = $scraper->getCrawler()->getRateLimit();
            if ($limit instanceof EnablingRateLimitInterface) {
                $limit->disable();
            }
        }

        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_NORMAL) {
            $dispatcher = $scraper->getEventDispatcher();
            $dispatcher->addSubscriber(new ScrapeOutputSubscriber($output));
        }

        try {
            $scraper->scrape($entity, $input->getArgument('url'));

            return 0;
        } catch (CrawlException $e) {
            $output->writeln("<error>Error scraping url: %s\n\n%s</error>", $e->getUrl(), $e->getMessage());

            return 1;
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
