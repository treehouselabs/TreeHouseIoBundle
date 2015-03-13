<?php

namespace TreeHouse\IoBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TreeHouse\IoBundle\Source\SourceManagerInterface;
use TreeHouse\IoBundle\Source\SourceProcessorInterface;

class SourceProcessCommand extends Command
{
    /**
     * @var SourceManagerInterface
     */
    protected $sourceManager;

    /**
     * @var SourceProcessorInterface
     */
    protected $sourceProcessor;

    /**
     * @param SourceManagerInterface   $sourceManager
     * @param SourceProcessorInterface $sourceProcessor
     */
    public function __construct(SourceManagerInterface $sourceManager, SourceProcessorInterface $sourceProcessor)
    {
        $this->sourceManager   = $sourceManager;
        $this->sourceProcessor = $sourceProcessor;

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('io:source:process');
        $this->addArgument('id', InputArgument::REQUIRED, 'The source id');
        $this->setDescription('Processes a source');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $id = $input->getArgument('id');

        if (null === $source = $this->sourceManager->findById($id)) {
            $output->writeln(sprintf('<error>Could not find source with id %d</error>', $id));

            return 1;
        }

        $linked = $this->sourceProcessor->isLinked($source);
        if (!$linked) {
            $output->writeln('Linking source first');
            $this->sourceProcessor->link($source);
        }

        $this->sourceProcessor->process($source);
        $this->sourceManager->flush($source);

        $output->writeln(sprintf('Source <info>%d</info> has been processed', $id));

        return 0;
    }
}
