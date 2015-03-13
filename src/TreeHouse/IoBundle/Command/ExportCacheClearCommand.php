<?php

namespace TreeHouse\IoBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TreeHouse\IoBundle\Export\FeedExporter;
use TreeHouse\IoBundle\Export\FeedType\AbstractFeedType;

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
        $this->addOption('type', 't', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'The type(s) to export feeds for. If left empty, feeds for all known types are exported.');
        $this->addOption('where', null, InputOption::VALUE_OPTIONAL, 'Limit the cache to a specific set of the query, use <comment>x</comment> as root alias');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $types = $this->getTypes($input->getOption('type'));

        foreach ($types as $type) {
            $builder = $type->getQueryBuilder('x');
            if ($input->getOption('where')) {
                $builder->andWhere($input->getOption('where'));
            }

            foreach ($builder->getQuery()->iterate() as list($item)) {
                $this->exporter->clearCache($item, [$type]);
            }
        }
    }

    /**
     * @param array $inputOption
     *
     * @return AbstractFeedType[]
     *
     * @throws \InvalidArgumentException
     */
    protected function getTypes(array $inputOption)
    {
        $types = $inputOption;

        if ([] === $types) {
            $types = $this->exporter->getTypes();
        } else {
            foreach ($types as &$type) {
                if (false === $this->exporter->hasType($type)) {
                    throw new \InvalidArgumentException(sprintf('%s not supported', $type));
                }

                $type = $this->exporter->getType($type);
            }
        }

        return $types;
    }
}
