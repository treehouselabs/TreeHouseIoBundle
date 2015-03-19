<?php

namespace TreeHouse\IoBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TreeHouse\IoBundle\Export\FeedExporter;
use TreeHouse\IoBundle\Export\FeedType\FeedTypeInterface;

class ExportCacheClearCommand extends Command
{
    /**
     * @var FeedExporter
     */
    protected $exporter;

    /**
     * @param FeedExporter $exporter
     */
    public function __construct(FeedExporter $exporter)
    {
        parent::__construct();

        $this->exporter = $exporter;
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('io:export:cache:clear');
        $this->addArgument('type', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 'The type(s) to export feeds for. If left empty, feeds for all known types are exported.');
        $this->addOption('where', null, InputOption::VALUE_OPTIONAL, 'Limit the cache to a specific set of the query, use <comment>x</comment> as root alias');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $types = $this->getTypes($input->getArgument('type'));

        $output->writeln(sprintf('Clearing cache for types: <info>%s</info>', implode(', ', $input->getArgument('type'))));

        foreach ($types as $type) {
            $builder = $type->getQueryBuilder('x');
            if ($input->getOption('where')) {
                $builder->andWhere($input->getOption('where'));
            }

            foreach ($builder->getQuery()->iterate() as list($item)) {
                $output->writeln(sprintf('Clearing cache for <comment>%s</comment>', $this->itemToString($item)));
                $this->exporter->clearCache($item, [$type]);

                $builder->getEntityManager()->detach($item);
            }
        }
    }

    /**
     * @param array $types
     *
     * @return FeedTypeInterface[]
     */
    protected function getTypes(array $types)
    {
        if (empty($types)) {
            return $this->exporter->getTypes();
        }

        $result = [];
        foreach ($types as &$type) {
            $result[] = $this->exporter->getType($type);
        }

        return $result;
    }

    /**
     * @param object $item
     *
     * @return string
     */
    private function itemToString($item)
    {
        if (method_exists($item, '__toString')) {
            return (string) $item;
        }

        if (method_exists($item, 'getId')) {
            return $item->getId();
        }

        return spl_object_hash($item);
    }
}
