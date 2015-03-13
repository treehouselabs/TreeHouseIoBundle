<?php

namespace TreeHouse\IoBundle\Command;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TreeHouse\IoBundle\Entity\Feed;
use TreeHouse\IoBundle\Model\OriginInterface;

class FeedListCommand extends Command
{
    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    /**
     * @param ManagerRegistry $doctrine
     */
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName('io:feed:list')
            ->setDescription('Queries/lists feed information')
            ->addOption('type', 't', InputOption::VALUE_OPTIONAL, 'The type of feeds to list')
            ->addOption('fields', 'f', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'The fields to list', ['id', 'type', 'origin', 'transportConfig'])
        ;
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $repo = $this->doctrine->getRepository('TreeHouseIoBundle:Feed');

        $builder = $repo->createQueryBuilder('f');
        $builder->select('f');

        if ($input->getOption('type')) {
            $builder
                ->andWhere('f.type = :type')
                ->setParameter('type', $input->getOption('type'))
            ;
        }

        $this->displayFeeds($builder->getQuery(), $output, $input->getOption('fields'));

        return 0;
    }

    /**
     * @param Query           $query
     * @param OutputInterface $output
     * @param array           $fields
     */
    protected function displayFeeds(Query $query, OutputInterface $output, array $fields)
    {
        $table = new Table($output);
        $table->setHeaders($fields);

        /** @var ClassMetadata $meta */
        $meta = $this->doctrine->getManager()->getClassMetadata('TreeHouseIoBundle:Feed');

        /** @var Feed $feed */
        foreach ($query->iterate() as list($feed)) {
            $row = [];
            foreach ($fields as $field) {
                $value       = $meta->getFieldValue($feed, $field);
                $row[$field] = $this->formatValue($value);
            }

            $table->addRow($row);
        }

        $table->render();
    }

    /**
     * @param mixed $value
     *
     * @return string
     */
    protected function formatValue($value)
    {
        if (is_scalar($value)) {
            return (string) $value;
        }

        if (is_object($value)) {
            if (method_exists($value, '__toString')) {
                return (string) $value;
            }

            if ($value instanceof OriginInterface) {
                return $value->getTitle();
            }
        }

        return json_encode($value, JSON_UNESCAPED_SLASHES);
    }
}
