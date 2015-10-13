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

class ScrapeStartCommand extends Command
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
        $this->setName('io:scrape:start');
        $this->setDescription('Starts scraper(s)');
        $this->addArgument('scraper', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'The scraper id');
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
        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_NORMAL) {
            $dispatcher = $this->factory->getEventDispatcher();
            $dispatcher->addSubscriber(new ScrapeOutputSubscriber($output));
        }

        $scrapers = $this->findScrapers($input->getArgument('scraper'));
        foreach ($scrapers as $scraperEntity) {
            $url = $scraperEntity->getUrl();

            $output->writeln(sprintf('Found scraper: <info>%s</info>', $url));
            $output->writeln(sprintf('- Start every <info>%s</info> hours', $scraperEntity->getStartFrequency()));

            if ($date = $scraperEntity->getDatetimeLastStarted()) {
                $output->writeln(sprintf('- Last started at <info>%s</info>', $date->format(DATE_RFC2822)));

                $nextStart = $date->add(new \DateInterval(sprintf('PT%sH', $scraperEntity->getStartFrequency())));
                if ($nextStart > new \DateTime()) {
                    $output->writeln(sprintf('- Next start time at <info>%s</info>', $nextStart->format(DATE_RFC2822)));

                    continue;
                }
            } else {
                $output->writeln('- Scraper has <info>never</info> started');
            }

            $output->writeln('Starting scraper...');
            $output->writeln('-------------------');

            try {
                $this->scrape($input, $scraperEntity);
            } catch (CrawlException $e) {
                $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
            }

            $output->writeln('-------------------');
            $output->writeln('');
        }
    }

    /**
     * @param integer[] $ids
     *
     * @return ScraperEntity[]
     */
    protected function findScrapers(array $ids)
    {
        $repo = $this->doctrine->getRepository('TreeHouseIoBundle:Scraper');

        if (!empty($ids)) {
            return $repo->findBy(['id' => $ids]);
        }

        return $repo->findAll();
    }

    /**
     * @param InputInterface $input
     * @param Scraper        $scraperEntity
     */
    protected function scrape(InputInterface $input, $scraperEntity)
    {
        $scraper = $this->factory->createScraper($scraperEntity);

        if ($input->getOption('async')) {
            $scraper->setAsync(true);
        }

        if ($input->getOption('no-limit')) {
            $limit = $scraper->getCrawler()->getRateLimit();
            if ($limit instanceof EnablingRateLimitInterface) {
                $limit->disable();
            }
        }

        $scraper->scrape($scraperEntity, $scraperEntity->getUrl());

        $scraperEntity->setDatetimeLastStarted(new \DateTime());
        $this->doctrine->getManager()->flush($scraperEntity);
    }
}
