<?php

namespace TreeHouse\IoBundle\Command;

use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\LockHandler;
use TreeHouse\IoBundle\Entity\Feed;
use TreeHouse\IoBundle\Import\Event\ImportEvent;
use TreeHouse\IoBundle\Import\ImportEvents;
use TreeHouse\IoBundle\Import\ImportRotator;

class ImportRotateCommand extends Command
{
    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    /**
     * @var ImportRotator
     */
    protected $importRotator;

    /**
     * @param ManagerRegistry $doctrine
     * @param ImportRotator   $importRotator
     */
    public function __construct(ManagerRegistry $doctrine, ImportRotator $importRotator)
    {
        $this->doctrine = $doctrine;
        $this->importRotator = $importRotator;

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('io:import:rotate');
        $this->addOption('rotations', 'r', InputOption::VALUE_OPTIONAL, 'The number of rotations to keep', 5);
        $this->setDescription('Rotates import logs');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $this->getName();
        $lockHandler = new LockHandler($name);
        if (!$lockHandler->lock()) {
            $output->writeln(
                sprintf('<info>%s</info> is still running, exiting.', $name)
            );

            return 0;
        }

        $dispatcher = $this->importRotator->getEventDispatcher();
        $dispatcher->addListener(ImportEvents::IMPORT_ROTATE, function (ImportEvent $event) use ($output) {
            $output->writeln(sprintf('<fg=red;option=bold>- import %d</>', $event->getImport()->getId()));
        });

        $query = $this->doctrine
            ->getRepository('TreeHouseIoBundle:Feed')
            ->createQueryBuilder('f')
            ->getQuery()
        ;

        /** @var Feed $feed */
        foreach ($query->iterate() as list($feed)) {
            $output->writeln(
                sprintf(
                    'Rotating imports for <comment>%s</comment> feed <info>%d</info>',
                    $feed->getOrigin()->getName(),
                    $feed->getId()
                )
            );

            $this->importRotator->rotate($feed, $input->getOption('rotations'));
        }

        $lockHandler->release();
    }
}
