<?php

namespace TreeHouse\IoBundle\Command;

use Doctrine\Common\Persistence\ManagerRegistry;
use PK\CommandExtraBundle\Command\Command;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TreeHouse\IoBundle\Entity\ImportPart;
use TreeHouse\IoBundle\EventListener\ImportOutputSubscriber;
use TreeHouse\IoBundle\Import\ImportFactory;

class ImportPartCommand extends Command
{
    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    /**
     * @var ImportFactory
     */
    protected $importFactory;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param ManagerRegistry $doctrine
     * @param ImportFactory   $importFactory
     * @param LoggerInterface $logger
     */
    public function __construct(ManagerRegistry $doctrine, ImportFactory $importFactory, LoggerInterface $logger)
    {
        $this->doctrine      = $doctrine;
        $this->importFactory = $importFactory;
        $this->logger        = $logger;

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('io:import:part');
        $this->addArgument('id', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'The id(s) of the part to import');
        $this->addOption('import', null, InputOption::VALUE_OPTIONAL, 'The id of the import you want to import parts for. This negates the <comment>id</comment> argument');
        $this->setDescription('Imports a part');
        $this->setSummarizeDefinition(['time' => true, 'memory' => true]);
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_NORMAL) {
            $dispatcher = $this->importFactory->getEventDispatcher();
            $dispatcher->addSubscriber(new ImportOutputSubscriber($output));
        }

        if ($importId = $input->getOption('import')) {
            $partIds = $this->getPartIds($importId);
        } elseif ($partIds = $input->getArgument('id')) {
        } else {
            throw new \RuntimeException('You must provide either a part id or an import id');
        }

        foreach ($partIds as $partId) {
            if (null === $part = $this->findPart($partId)) {
                $output->writeln(sprintf('<error>Part with id "%d" does not exist</error>', $partId));

                continue;
            }

            $this->runImportPart($output, $part);
        }

        return 0;
    }

    /**
     * @param OutputInterface $output
     * @param ImportPart      $part
     */
    protected function runImportPart(OutputInterface $output, ImportPart $part)
    {
        $output->writeln(
            sprintf(
                'Importing part <comment>%d</comment> of import <comment>%d</comment>',
                $part->getPosition(),
                $part->getImport()->getId()
            )
        );

        $job = $this->importFactory->createImportJob($part);
        $job->setLogger($this->logger);
        $job->run();
    }

    /**
     * @param integer $importId
     *
     * @return ImportPart[]
     */
    protected function getPartIds($importId)
    {
        return $this->doctrine->getRepository('TreeHouseIoBundle:ImportPart')->findUnstartedByImport($importId);
    }

    /**
     * @param integer $id
     *
     * @return ImportPart
     */
    protected function findPart($id)
    {
        return $this->doctrine->getRepository('TreeHouseIoBundle:ImportPart')->find($id);
    }
}
