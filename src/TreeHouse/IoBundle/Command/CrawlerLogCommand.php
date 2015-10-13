<?php

namespace TreeHouse\IoBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TreeHouse\IoBundle\Scrape\Crawler\CrawlerInterface;
use TreeHouse\IoBundle\Scrape\ScraperFactory;

class CrawlerLogCommand extends Command
{
    /**
     * @var ScraperFactory
     */
    protected $factory;

    /**
     * @param ScraperFactory $factory
     */
    public function __construct(ScraperFactory $factory)
    {
        $this->factory = $factory;

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('io:crawler:log');
        $this->setDescription('Shows the logged requests for a crawler');
        $this->addArgument('crawler', InputArgument::REQUIRED, 'The crawler name');
        $this->addOption('since', null, InputOption::VALUE_OPTIONAL, 'The time interval to get the logged requests for', '1 hour');
        $this->addOption('all', 'a', InputOption::VALUE_NONE, 'Show all logged requests, negates <comment>--since</comment>');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $date = $input->getOption('all') ? null : new \DateTime('-' . $input->getOption('since'));

        $crawler = $this->findCrawler($input->getArgument('crawler'));

        $output->writeln(
            sprintf(
                'Showing logged requests for crawler <info>%s</info> since <comment>%s</comment>',
                $input->getArgument('crawler'),
                $date ? $date->format(DATE_ISO8601) : 'the beginning'
            )
        );

        $requests = $crawler->getLogger()->getRequestsSince($date);

        $output->writeln(sprintf('Found <info>%d</info> requests:', sizeof($requests)));

        foreach ($requests as list($timestamp, $request)) {
            $output->writeln(
                sprintf(
                    '[<info>%s</info>]: <comment>%s</comment>',
                    date(DATE_ISO8601, $timestamp),
                    $request
                )
            );
        }
    }

    /**
     * @param string $crawler
     *
     * @return CrawlerInterface
     */
    protected function findCrawler($crawler)
    {
        return $this->factory->getCrawler($crawler);
    }
}
