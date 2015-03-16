<?php

namespace TreeHouse\IoBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TreeHouse\IoBundle\Entity\ImportPart;
use TreeHouse\IoBundle\Import\ImportScheduler;
use TreeHouse\IoBundle\Import\Processor\ProcessorInterface;

class ImportRescheduleCommand extends Command
{
    /**
     * @var ImportScheduler
     */
    protected $scheduler;

    /**
     * @var ProcessorInterface
     */
    protected $processor;

    /**
     * @param ImportScheduler    $scheduler
     * @param ProcessorInterface $processor
     */
    public function __construct(ImportScheduler $scheduler, ProcessorInterface $processor)
    {
        $this->scheduler = $scheduler;
        $this->processor = $processor;

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('io:import:reschedule');
        $this->addOption('import', 'i', InputOption::VALUE_OPTIONAL, 'A specific import you want to reschedule parts for');
        $this->setDescription('Reschedules eligible import parts');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($importId = $input->getOption('import')) {
            $output->writeln(sprintf('Rescheduling parts for import <info>%s</info>', $importId));
            $parts = $this->scheduler->findPartsByImportId($importId);
            $this->scheduleParts($parts, $output);
        } else {
            $output->writeln('Rescheduling parts that have started, but not finished');
            $parts = $this->scheduler->findUnfinishedParts();
            $this->scheduleParts($parts, $output);

            $output->writeln('Rescheduling parts that should have started, but didn\'t');
            $parts = $this->scheduler->findOverdueParts();
            $this->scheduleParts($parts, $output);
        }
    }

    /**
     * @param ImportPart[]    $parts
     * @param OutputInterface $output
     */
    protected function scheduleParts(array $parts, OutputInterface $output)
    {
        foreach ($parts as $part) {
            if ($this->processor->isRunning($part)) {
                continue;
            }

            $this->scheduler->schedulePart($part);

            $output->writeln(
                sprintf(
                    'Rescheduled part <comment>%d</comment> of <comment>%s</comment> import with id <comment>%s</comment>',
                    $part->getPosition(),
                    $part->getImport()->getFeed()->getOrigin()->getTitle(),
                    $part->getImport()->getId()
                )
            );
        }
    }
}
