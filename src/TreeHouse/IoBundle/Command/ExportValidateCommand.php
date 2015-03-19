<?php

namespace TreeHouse\IoBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use TreeHouse\IoBundle\Export\FeedExporter;
use TreeHouse\IoBundle\Export\FeedType\FeedTypeInterface;

class ExportValidateCommand extends Command
{
    /**
     * @var FeedExporter
     */
    protected $exporter;

    /**
     * @var \XMLReader
     */
    protected $reader;

    /**
     * @var string
     */
    protected $currentItem;

    /**
     * @param FeedExporter $exporter
     */
    public function __construct(FeedExporter $exporter)
    {
        $this->exporter = $exporter;

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->addArgument('type', InputArgument::REQUIRED, 'The type for which the feed needs to be validated');
        $this->setName('io:export:validate');
        $this->setDescription('Validate an exported feed');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $type = $this->exporter->getType($input->getArgument('type'));

        try {
            $this->validate($type, $output);
        } catch (\RuntimeException $exception) {
            $output->writeln('');
            $output->writeln(sprintf('<error>%s</error>', $exception->getMessage()));
            $output->writeln('');
            $output->writeln('Node:');
            $output->writeln($this->currentItem);

            return 1;
        }

        $output->writeln('');
        $output->writeln(sprintf('<info>Feed "%s" is valid!</info>', $type->getName()));

        return 0;
    }

    /**
     * @param FeedTypeInterface $type
     * @param OutputInterface   $output
     *
     * @return int
     */
    protected function validate(FeedTypeInterface $type, OutputInterface $output)
    {
        $file = $this->exporter->getFeedFilename($type);

        if (!file_exists($file)) {
            throw new FileNotFoundException(sprintf('<error>Feed "%s" has not yet been exported</error>', $type->getName()));
        }

        $options = LIBXML_NOENT | LIBXML_COMPACT | LIBXML_PARSEHUGE | LIBXML_NOERROR | LIBXML_NOWARNING;
        $this->reader = new \XMLReader($options);
        $this->reader->open($file);
        $this->reader->setParserProperty(\XMLReader::SUBST_ENTITIES, true);
//        foreach ($type->getNamespaces() as $name => $location) {
//            $this->reader->setSchema($location);
//        }

        libxml_clear_errors();
        libxml_use_internal_errors(true);
        libxml_disable_entity_loader(true);

        $progress = new ProgressBar($output);
        $progress->start();

        // go through the whole thing
        while ($this->reader->read()) {
            if ($this->reader->nodeType === \XMLReader::ELEMENT && $this->reader->name === $type->getItemNode()) {
                $progress->advance();
                $this->currentItem = $this->reader->readOuterXml();
            }

            if ($error = libxml_get_last_error()) {
                throw new \RuntimeException(
                    sprintf(
                        '[%s %s] %s (in %s - line %d, column %d)',
                        LIBXML_ERR_WARNING === $error->level ? 'WARNING' : 'ERROR',
                        $error->code,
                        trim($error->message),
                        $error->file ? $error->file : 'n/a',
                        $error->line,
                        $error->column
                    )
                );
            }
        }

        $progress->finish();
    }
}
