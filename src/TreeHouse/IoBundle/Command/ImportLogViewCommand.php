<?php

namespace TreeHouse\IoBundle\Command;

use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TreeHouse\IoBundle\Entity\Import;
use TreeHouse\IoBundle\Entity\ImportRepository;
use TreeHouse\IoBundle\Import\Log\ItemLoggerInterface;

class ImportLogViewCommand extends Command
{
    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    /**
     * @var ItemLoggerInterface
     */
    protected $itemLogger;

    /**
     * @param ManagerRegistry     $doctrine
     * @param ItemLoggerInterface $itemLogger
     */
    public function __construct(ManagerRegistry $doctrine, ItemLoggerInterface $itemLogger)
    {
        $this->doctrine = $doctrine;
        $this->itemLogger = $itemLogger;

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('io:import:log:view');
        $this->addArgument('import', InputArgument::REQUIRED, 'The id of the import');
        $this->setDescription('View an import log');
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

        $items = 0;
        $success = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($this->itemLogger->getImportedItems($import) as $item) {
            $output->writeln(sprintf('<info>%s</info>:', $item['item']));

            ++$items;

            foreach ($item as $key => $value) {
                if ($key === 'item') {
                    continue;
                }

                $output->writeln(sprintf('  <info>%s</info>: <comment>%s</comment>', $key, $value));
            }

            switch ($item['result']) {
                case 'success':
                    $success++;
                    break;
                case 'failed':
                    $failed++;
                    break;
                case 'skipped':
                    $skipped++;
                    break;
            }
        }
        $output->writeln('');
        $output->writeln(sprintf('Imported <info>%s</info> items', $items));
        $output->writeln(sprintf('- succes:  <info>%s</info> (<info>%.2f%%</info>)', $success, $success / $items * 100));
        $output->writeln(sprintf('- failed:  <info>%s</info> (<info>%.2f%%</info>)', $failed, $failed / $items * 100));
        $output->writeln(sprintf('- skipped: <info>%s</info> (<info>%.2f%%</info>)', $skipped, $skipped / $items * 100));

        return 0;
    }

    /**
     * @param int $id
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
