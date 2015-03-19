<?php

namespace TreeHouse\IoBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TreeHouse\IoBundle\Export\FeedExporter;

class ExportInspectCommand extends Command
{
    const EVALUATE_COUNT      = 'count';
    const EVALUATE_EXPRESSION = 'expression';

    /**
     * @var FeedExporter
     */
    protected $exporter;

    /**
     * @var \XMLReader
     */
    protected $reader;

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
        $this->addArgument('type', InputArgument::REQUIRED, 'The type for which the feed needs to be inspected');
        $this->addOption('filter', null, InputOption::VALUE_OPTIONAL, 'XPath expression to filter nodes, use <comment>x</comment> as the namespace alias', '//*');
        $this->addOption('expression', null, InputOption::VALUE_OPTIONAL, 'XPath expression to evaluate nodes, use <comment>x</comment> as the namespace alias');
        $this->addOption(
            'evaluate',
            null,
            InputOption::VALUE_OPTIONAL,
            sprintf(
                'Method to use for evaluation, supported methods are <info>%s</info> and <info>%s</info>. Defaults to <info>%s</info>',
                self::EVALUATE_COUNT,
                self::EVALUATE_EXPRESSION,
                self::EVALUATE_COUNT
            )
        );
        $this->setName('io:export:inspect');
        $this->setDescription('Inspect/query an exported feed');
        $this->setHelp(<<<EOT
This command inspects an exported feed. It walks through the entire feed,
optionally applying a filter to only include specific items. After collecting
all matched items it evaluates the output. The evaluated result is then
displayed in the console.

Evaluation can be two options:

    1. <comment>count</comment>: counts the number of matched items.
    2. <comment>expression</comment>: executes a given XPath expression against each matched items.

The expressions in the <comment>--filter</comment> and <comment>--expression</comment> options
both have a namespace registered with the alias <comment>x</comment>, which you need to use
when targeting nodes.

<option=bold>Examples:</>

Counting the number of items in the <comment>acme</comment> feed:

  $ <info>php app/console %command.name% acme</info>
  # 1234

Check if an item with a specific id attribute exists in the feed:

  $ <info>php app/console %command.name% acme --filter="@id=1234"</info>
  # 1

Counting all items without any photos (ie: an empty <comment>photos</comment> node,
in which there would normally be one or more <comment>photo</comment> subnodes:

  $ <info>php app/console %command.name% acme --filter="count(x:photos/x:photo) < 1"</info>
  # 345

Selecting the id of all items with title "foo":

  $ <info>php app/console %command.name% acme --filter="x:title[.='foo']" --evaluate=expression --expression="@id"</info>
  # 34547
  # 67878
  # 12945
  # 48784
  # 56978
  # ...etc

EOT
        );
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $type = $this->exporter->getType($input->getArgument('type'));
        $file = $this->exporter->getFeedFilename($type);

        if (!file_exists($file)) {
            $output->writeln(sprintf('<error>Feed "%s" has not yet been exported</error>', $type->getName()));

            return 1;
        }

        $evaluationMethod     = $input->getOption('evaluate');
        $evaluationExpression = $input->getOption('expression');

        if (!$evaluationMethod && $evaluationExpression) {
            $evaluationMethod = self::EVALUATE_EXPRESSION;
        }

        if (!$evaluationMethod) {
            $evaluationMethod = self::EVALUATE_COUNT;
        }

        list($results, $total) = $this->inspect($output, new \SplFileInfo($file), $input->getOption('filter'));

        switch ($evaluationMethod) {
            case self::EVALUATE_COUNT:
                if ($evaluationExpression) {
                    throw new \InvalidArgumentException('You cannot use an expression when using count evaluation');
                }
                $this->evaluateCount($output, $results, $total);

                break;
            case self::EVALUATE_EXPRESSION:
                $this->evaluateResult($output, $evaluationExpression, $results, $total);

                break;

            default:
                throw new \InvalidArgumentException(sprintf('Invalid evaluation method: %s', $evaluationMethod));
        }

        return 0;
    }

    /**
     * @param OutputInterface $output
     * @param \SplFileInfo    $feed
     * @param string          $filterExpression
     *
     * @return array<array, integer>
     */
    protected function inspect(OutputInterface $output, \SplFileInfo $feed, $filterExpression)
    {
        $options = LIBXML_NOENT | LIBXML_NONET | LIBXML_COMPACT | LIBXML_PARSEHUGE | LIBXML_NOERROR | LIBXML_NOWARNING;
        $this->reader = new \XMLReader($options);
        $this->reader->open($feed->getPathname());
        $this->reader->setParserProperty(\XMLReader::SUBST_ENTITIES, true);

        libxml_clear_errors();
        libxml_use_internal_errors(true);
        libxml_disable_entity_loader(true);

        $total = 0;
        $results = [];

        $output->writeln(
            sprintf('Reading <comment>%s</comment>', $feed->getFilename())
        );

        if ($filterExpression) {
            $output->writeln(sprintf('Filtering nodes with expression "<info>%s</info>"', $filterExpression));
        }

        $progress = new ProgressBar($output);
        $progress->start();

        // go through the whole thing
        while ($this->reader->read()) {
            if ($this->reader->nodeType === \XMLReader::ELEMENT && $this->reader->name === 'listing') {
                $progress->advance();
                $total++;

                $node = $this->reader->expand();
                $doc = new \DOMDocument();
                $doc->appendChild($node);

                $xpath = new \DOMXPath($doc);
                $xpath->registerNamespace('x', $doc->lookupNamespaceUri($doc->namespaceURI));
                $query = $xpath->evaluate($filterExpression, $node);

                $result = $query instanceof \DOMNodeList ? $query->length : !empty($query);
                if ($result) {
                    $results[] = $node;
                }
            }
        }

        $progress->finish();
        $output->writeln('');

        return [$results, $total];
    }

    /**
     * @param OutputInterface $output
     * @param array           $results
     * @param integer         $total
     */
    protected function evaluateCount(OutputInterface $output, array $results, $total)
    {
        $results = sizeof($results);

        $output->writeln(
            sprintf(
                '<info>%d</info>/<info>%d</info> nodes (<info>%d%%</info>) match your expression:',
                $results,
                $total,
                round($results / $total * 100)
            )
        );
    }

    /**
     * @param OutputInterface $output
     * @param string          $evaluationExpression
     * @param array           $results
     * @param integer         $total
     */
    protected function evaluateResult(OutputInterface $output, $evaluationExpression, array $results, $total)
    {
        $msg = sprintf(
            '<info>%d</info>/<info>%d</info> nodes (<info>%d%%</info>) match your expression:',
            sizeof($results),
            $total,
            round(sizeof($results) / $total * 100)
        );

        $output->writeln($msg);
        $output->writeln(str_pad('', strlen(strip_tags($msg)), '-', STR_PAD_LEFT));

        /** @var \DOMNode $node */
        foreach ($results as $node) {
            $xpath = new \DOMXPath($node->ownerDocument);
            $xpath->registerNamespace('x', $node->ownerDocument->lookupNamespaceUri($node->ownerDocument->namespaceURI));
            $evaluation = $xpath->evaluate($evaluationExpression, $node);

            $output->writeln($this->serialize($evaluation));
        }
    }

    /**
     * @param mixed $result
     *
     * @return string
     */
    protected function serialize($result)
    {
        if ($result instanceof \DOMNodeList) {
            $serialized = [];
            foreach ($result as $node) {
                $serialized[] = $this->serialize($node);
            }

            $result = $serialized;
        }

        if ($result instanceof \DOMAttr) {
            $result = $result->value;
        }

        if ($result instanceof \DOMNode) {
            $result = preg_replace('/ xmlns(\:xsi)?="[^"]+"/', '', $result->C14N());
        }

        if (is_array($result) && is_numeric(key($result))) {
            return implode(', ', array_map([$this, 'serialize'], $result));
        }

        return is_scalar($result) ? (string) $result : json_encode($result);
    }
}
