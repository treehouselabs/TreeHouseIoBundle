<?php

namespace TreeHouse\IoBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use TreeHouse\Feeder\Feed;
use TreeHouse\Feeder\Reader\XmlReader;
use TreeHouse\Feeder\Resource\FileResource;
use TreeHouse\IoBundle\Import\Feed\TransportFactory;

class FeedInspectCommand extends Command
{
    /**
     * @var array
     */
    protected $data = [];

    /**
     * @var array
     */
    protected $values = [];

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName('io:feed:inspect')
            ->addArgument('url', InputArgument::REQUIRED, 'The url of the feed to inspect.')
            ->addOption('node', null, InputOption::VALUE_REQUIRED, 'The item node name.')
            ->addOption('limit', null, InputOption::VALUE_OPTIONAL, 'Number of distinct values per key to display', 20)
            ->setDescription('Inspects a feed and displays information about it')
        ;
    }

    /**
     * @inheritdoc
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        if (!$input->getOption('node')) {
            $helper = new QuestionHelper();
            $question = new Question('Enter the name of the node that contains a property in this feed: ');

            $node = $helper->ask($input, $output, $question);
            $input->setOption('node', $node);
        }
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $url = $input->getArgument('url');
        $node = $input->getOption('node');
        $limit = $input->getOption('limit');

        $feed = $this->getFeed($url, $node);

        $this->inspect($feed);
        $this->report($output, $limit);

        return 0;
    }

    /**
     * @param string $url
     * @param string $node
     *
     * @return Feed
     */
    protected function getFeed($url, $node)
    {
        $transport = TransportFactory::createTransportFromUrl($url);
        $reader = new XmlReader(new FileResource($transport));
        $reader->setNodeCallback($node);

        return new Feed($reader);
    }

    /**
     * @param Feed $feed
     */
    protected function inspect(Feed $feed)
    {
        while ($item = $feed->getNextItem()) {
            foreach ($item->all() as $key => $value) {
                if (!array_key_exists($key, $this->data)) {
                    $this->data[$key] = [];
                }

                $hash = md5(serialize($value));

                if (!array_key_exists($hash, $this->data[$key])) {
                    $this->data[$key][$hash] = 0;
                }

                ++$this->data[$key][$hash];

                if (!array_key_exists($hash, $this->values)) {
                    $this->values[$hash] = $value;
                }
            }
        }
    }

    /**
     * @param OutputInterface $output
     * @param int             $limit
     */
    protected function report(OutputInterface $output, $limit)
    {
        $output->writeln('Report for feed:');
        $output->writeln('');

        $valueLength = 50;

        foreach ($this->data as $key => $hashes) {
            $output->writeln(sprintf('Statistics for node <info>%s</info>:', $key));
            $output->writeln('');

            arsort($hashes, SORT_NUMERIC);

            $output->writeln(sprintf('%s | %s', str_pad('Value', $valueLength, ' ', STR_PAD_RIGHT), 'Count'));
            $output->writeln(sprintf('%s+%s', str_pad('', $valueLength + 1, '-'), str_pad('', 15, '-')));

            $counter = 0;
            $notShown = 0;

            foreach ($hashes as $hash => $count) {
                if ($counter > $limit) {
                    ++$notShown;
                    continue;
                }

                $value = $this->arrayToString($this->values[$hash]);
                $lines = explode("\n", wordwrap($value, $valueLength, "\n", true));

                $firstLine = array_shift($lines);
                $output->writeln(sprintf('%s | %s', str_pad($firstLine, $valueLength, ' ', STR_PAD_RIGHT), $count));

                $numLines = count($lines);
                $lineLimit = 2;

                foreach (array_slice($lines, 0, 2) as $n => $line) {
                    $ellipses = '';
                    if ($lineLimit - 1 === $n) {
                        $ellipses = $numLines > 2 ? ' (...)' : '';
                        $line = substr($line, 0, -5);
                    }

                    $output->writeln($line . $ellipses);
                }

                ++$counter;
            }

            if ($notShown > 0) {
                $output->writeln(sprintf('And <info>%d</info> more...', $notShown));
            }

            $output->writeln('');
            $output->writeln('');
        }
    }

    /**
     * @param mixed $value
     *
     * @return string
     */
    protected function arrayToString($value)
    {
        if (!is_array($value)) {
            return $value;
        }

        foreach ($value as &$subvalue) {
            $subvalue = $this->arrayToString($subvalue);
        }

        return implode(', ', $value);
    }
}
