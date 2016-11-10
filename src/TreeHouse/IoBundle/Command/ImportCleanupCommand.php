<?php

namespace TreeHouse\IoBundle\Command;

use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @deprecated to be removed in 3.0
 */
class ImportCleanupCommand extends Command
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
        $this->setName('io:import:cleanup');
        $this->setDescription('[DEPRECATED] Cleans up imports that are not correct');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Removing imports without any parts</info>');

        $doctrine = $this->doctrine->getManager();
        $imports = $doctrine->getRepository('TreeHouseIoBundle:Import')->findByNumberOfParts(0);

        foreach ($imports as $import) {
            $output->writeln(
                sprintf(
                    'Cleaning up import <info>%d</info>',
                    $import->getId()
                )
            );

            $doctrine->remove($import);
            $doctrine->flush($import);
        }

        $doctrine->flush();
    }
}
