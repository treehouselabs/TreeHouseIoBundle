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
use TreeHouse\IoBundle\Model\SourceInterface;
use TreeHouse\IoBundle\Scrape\Exception\CrawlException;
use TreeHouse\IoBundle\Scrape\SourceRevisitor;
use TreeHouse\IoBundle\Source\SourceManagerInterface;

class ScrapeRevisitCommand extends Command
{
    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    /**
     * @var SourceManagerInterface
     */
    protected $sourceManager;

    /**
     * @var SourceRevisitor
     */
    protected $revisitor;

    /**
     * @param ManagerRegistry        $doctrine
     * @param SourceManagerInterface $sourceManager
     * @param SourceRevisitor        $revisitor
     */
    public function __construct(ManagerRegistry $doctrine, SourceManagerInterface $sourceManager, SourceRevisitor $revisitor)
    {
        $this->doctrine      = $doctrine;
        $this->sourceManager = $sourceManager;
        $this->revisitor     = $revisitor;

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('io:scrape:revisit');
        $this->setDescription('Revisits earlier scraped sources to see if they still exist');
        $this->addArgument('scrapers', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 'Limit the sources to specific scraper id(s)');
        $this->addOption(
            'async',
            'a',
            InputOption::VALUE_NONE,
            'Whether to revisit asynchronous. Doing so will queue sources, rather them revisiting them right away'
        );
        $this->addOption('no-limit', null, InputOption::VALUE_NONE, 'Disables the rate limit');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $async    = $input->getOption('async');
        $noLimit  = $input->getOption('no-limit');
        $scrapers = $this->findScrapers($input->getArgument('scrapers'));

        foreach ($scrapers as $scraperEntity) {
            $date = new \DateTime(sprintf('-%d hours', $scraperEntity->getRevisitFrequency()));

            $builder = $this->sourceManager->getRepository()->queryByScraperAndUnvisitedSince($scraperEntity, $date);
            foreach ($builder->getQuery()->iterate() as list($source)) {
                /** @var SourceInterface $source */
                try {
                    $output->writeln(sprintf('Revisiting <info>%s</info>', $source->getOriginalUrl()));

                    if ($async) {
                        $this->revisitor->revisitAfter($source, new \DateTime());
                    } else {
                        $this->revisitor->revisit($source, $async, $noLimit);
                    }
                } catch (CrawlException $e) {
                    $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
                }
            }
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
}
