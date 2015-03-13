<?php

namespace TreeHouse\IoBundle\Command;

use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TreeHouse\IoBundle\Entity\Import;
use TreeHouse\IoBundle\Entity\ImportRepository;

class ImportCloseCommand extends Command
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
        $this->setName('io:import:close');
        $this->addArgument('import', InputArgument::REQUIRED, 'The id of the import');
        $this->setDescription('Closes an import');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (null === $import = $this->findImportById($input->getArgument('import'))) {
            $output->writeln(sprintf('<error>Import %d does not exist</error>', $input->getArgument('import')));

            return 1;
        }

        // start import if it hasn't already
        if (!$import->isStarted()) {
            $output->writeln(sprintf('Starting import <comment>%d</comment> before closing it', $import->getId()));
            $this->getRepository()->startImport($import);
        }

        if ($import->isFinished()) {
            $output->writeln(sprintf('Import <comment>%d</comment> is already finished', $import->getId()));

            return 1;
        }

        $output->writeln(sprintf('Closing import <comment>%d</comment>', $import->getId()));

        if ($this->getRepository()->importHasUnfinishedParts($import)) {
            $output->writeln('Closing unfinished parts first');

            foreach ($import->getParts() as $part) {
                $output->writeln(sprintf('Closing part <comment>%d</comment>', $part->getPosition()));

                if (!$part->isStarted()) {
                    $part->setProcess(0);
                    $this->getRepository()->startImportPart($part);
                }

                if (!$part->isFinished()) {
                    $this->getRepository()->finishImportPart($part);
                }
            }
        }

        $this->getRepository()->finishImport($import);

        $output->writeln('<info>Import closed</info>');

        return 0;
    }

    /**
     * @param integer $id
     *
     * @return Import
     */
    protected function findImportById($id)
    {
        return $this->getRepository()->find($id);
    }

    /**
     * @return ImportRepository
     */
    protected function getRepository()
    {
        return $this->doctrine->getRepository('TreeHouseIoBundle:Import');
    }
}
