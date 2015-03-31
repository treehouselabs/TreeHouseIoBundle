<?php

namespace TreeHouse\IoBundle\Scrape\Parser\Type;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\OptionsResolver\OptionsResolver;
use TreeHouse\Feeder\Modifier\Data\Transformer\DateTimeToIso8601Transformer;
use TreeHouse\Feeder\Modifier\Data\Transformer\EmptyValueToNullTransformer;
use TreeHouse\Feeder\Modifier\Data\Transformer\StringToBooleanTransformer;
use TreeHouse\Feeder\Modifier\Data\Transformer\TraversingTransformer;
use TreeHouse\Feeder\Modifier\Item\Transformer\ObsoleteFieldsTransformer;
use TreeHouse\Feeder\Modifier\Item\Transformer\TrimTransformer;
use TreeHouse\Feeder\Modifier\Item\Transformer\UnderscoreKeysTransformer;
use TreeHouse\IoBundle\Entity\Scraper;
use TreeHouse\IoBundle\Item\Modifier\Data\Transformer\DutchStringToDateTimeTransformer;
use TreeHouse\IoBundle\Item\Modifier\Data\Transformer\EntityToIdTransformer;
use TreeHouse\IoBundle\Item\Modifier\Data\Transformer\ForeignMappingTransformer;
use TreeHouse\IoBundle\Item\Modifier\Data\Transformer\LocalizedStringToNumberTransformer;
use TreeHouse\IoBundle\Item\Modifier\Data\Transformer\MultiLineToSingleLineTransformer;
use TreeHouse\IoBundle\Item\Modifier\Data\Transformer\MultiSpaceToSingleSpaceTransformer;
use TreeHouse\IoBundle\Item\Modifier\Data\Transformer\NormalizedStringTransformer;
use TreeHouse\IoBundle\Item\Modifier\Data\Transformer\StringToDateTimeTransformer;
use TreeHouse\IoBundle\Item\Modifier\Item\Filter\BlockedSourceFilter;
use TreeHouse\IoBundle\Item\Modifier\Item\Filter\ModifiedItemFilter;
use TreeHouse\IoBundle\Item\Modifier\Item\Transformer\DefaultValuesTransformer;
use TreeHouse\IoBundle\Item\Modifier\Item\Validator\OriginIdValidator;
use TreeHouse\IoBundle\Scrape\Modifier\Item\Mapper\NodeMapper;
use TreeHouse\IoBundle\Scrape\Modifier\Item\Mapper\ScrapedItemBagMapper;
use TreeHouse\IoBundle\Scrape\Parser\ParserBuilderInterface;
use TreeHouse\IoBundle\Source\Manager\ImportSourceManager;

abstract class AbstractParserType implements ParserTypeInterface
{
    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    /**
     * @var ImportSourceManager
     */
    protected $sourceManager;

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @param ManagerRegistry     $doctrine
     * @param ImportSourceManager $sourceManager
     */
    public function __construct(ManagerRegistry $doctrine, ImportSourceManager $sourceManager)
    {
        $this->doctrine      = $doctrine;
        $this->sourceManager = $sourceManager;
    }

    /**
     * {@inheritDoc}
     */
    public function setOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired([
            'forced',
            'scraper',
            'date_locale',
            'number_locale',
            'default_values',
        ]);

        $resolver->setDefaults([
            'forced'         => false,
            'date_locale'    => 'en',
            'number_locale'  => 'en',
            'default_values' => [],
        ]);

        $resolver->setAllowedTypes('forced', 'bool');
        $resolver->setAllowedTypes('scraper', Scraper::class);
        $resolver->setAllowedTypes('default_values', 'array');

        $resolver->setAllowedValues('date_locale', ['en', 'nl']);
        $resolver->setAllowedValues('number_locale', ['en', 'nl']);
    }

    /**
     * @inheritdoc
     */
    public function build(ParserBuilderInterface $parser, array $options)
    {
        $this->options = $options;

        // set original id/url on the item
        $parser->addModifierBetween(new ScrapedItemBagMapper($this->getOriginalIdCallback(), $this->getOriginalUrlCallback(), $this->getModificationDateCallback()), 100, 200);

        // range 300-400: perform validation and checks for skipping early on
        $parser->addModifierBetween(new OriginIdValidator(), 300, 400);
        $parser->addModifierBetween(new BlockedSourceFilter($this->sourceManager), 300, 400);

        // check for modification dates, but only when not forced
        if ($options['forced'] === false) {
            $parser->addModifierBetween(new ModifiedItemFilter($this->sourceManager), 300, 400);
        }

        // 2000-3000: map paths
        $parser->addModifier(new NodeMapper($this->getMapping()), 2000);
        $parser->addModifierBetween(new TrimTransformer(), 2000, 3000);

        // 3000-3500: transform specific fields

        // 3500-4000: feed-type specific: reserved for field transformers before the regular transformers

        // 4000-5000: reserved for transformers added automatically based on entity field mapping
        $this->addEntityModifiers($parser, 4000, 5000);

        // 5000-6000: feed-type specific: reserved for field transformers after the regular transformers

        // 6000-7000: reserved for modifiers after all other modifiers are done
        $this->addFinalModifiers($parser, 6000, 7000);

        // give extending feed type a method for custom modifiers
        $this->addCustomModifiers($parser, $options);
    }

    /**
     * @return array
     */
    abstract protected function getMapping();

    /**
     * Returns mapping for an association, or null if it does not exist
     *
     * @param string $association
     *
     * @return array|null
     */
    abstract protected function getAssociationMapping($association);

    /**
     * Returns mapping for a field, or null if it does not exist
     *
     * @param string $field
     *
     * @return array|null
     */
    abstract protected function getFieldMapping($field);

    /**
     * Returns an array with all the field/association names for
     * the entity that is imported.
     *
     * @return array
     */
    abstract protected function getEntityFields();

    /**
     * Specify a mapping here from foreign configuration to our configuration
     *
     * @return array
     */
    abstract protected function getForeignMapping();

    /**
     * @return \Closure
     */
    protected function getOriginalIdCallback()
    {
        return function (Crawler $crawler) {
            if ($selector = $this->getOriginalIdSelector()) {
                if (preg_match('/^\/\//', $selector)) {
                    $node = $crawler->filterXPath($selector);
                } else {
                    $node = $crawler->filter($selector);
                }

                return $node->html();
            }

            return null;
        };
    }

    /**
     * @return \Closure
     */
    protected function getOriginalUrlCallback()
    {
        return function (Crawler $crawler) {
            if ($selector = $this->getOriginalUrlSelector()) {
                if (preg_match('/^\/\//', $selector)) {
                    $node = $crawler->filterXPath($selector);
                } else {
                    $node = $crawler->filter($selector);
                }

                return $node->html();
            }

            return null;
        };
    }

    /**
     * @return \Closure
     */
    protected function getModificationDateCallback()
    {
        return function (Crawler $crawler) {
            if ($selector = $this->getModificationDateSelector()) {
                if (preg_match('/^\/\//', $selector)) {
                    $node = $crawler->filterXPath($selector);
                } else {
                    $node = $crawler->filter($selector);
                }

                if ($date = $node->html()) {
                    return new \DateTime($date);
                }
            }

            return null;
        };
    }

    /**
     * Override this method to add custom modifiers to the feed
     *
     * @param ParserBuilderInterface $parser
     * @param array                  $options
     */
    protected function addCustomModifiers(ParserBuilderInterface $parser, array $options)
    {
    }

    /**
     * Automatically adds modifiers based on entity field/association mapping
     *
     * @param ParserBuilderInterface $parser
     * @param integer                $startIndex
     * @param integer                $endIndex
     */
    protected function addEntityModifiers(ParserBuilderInterface $parser, $startIndex, $endIndex)
    {
        // skip id
        $mappedFields = array_filter($this->getMappedFields(), function ($value) {
            return $value !== 'id';
        });

        foreach ($mappedFields as $key) {
            // see if association is in meta
            if (null !== $mapping = $this->getAssociationMapping($key)) {
                $this->addAssociationModifiers($parser, $key, $mapping, $startIndex, $endIndex);
                continue;
            }

            // see if field is in meta
            if (null !== $mapping = $this->getFieldMapping($key)) {
                $this->addFieldModifiers($parser, $key, $mapping, $startIndex, $endIndex);
                continue;
            }
        }
    }

    /**
     * @param ParserBuilderInterface $parser
     * @param string                 $association The association name
     * @param array                  $mapping     The association mapping
     * @param integer                $startIndex
     * @param integer                $endIndex
     *
     * @return integer The updated index
     */
    protected function addAssociationModifiers(
        ParserBuilderInterface $parser,
        $association,
        array $mapping,
        $startIndex,
        $endIndex
    ) {
        $transformer = new EntityToIdTransformer($this->getEntityManager());

        if ($mapping['type'] & ClassMetadataInfo::TO_MANY) {
            $transformer = new TraversingTransformer($transformer);
        }

        $parser->addTransformerBetween($transformer, $association, $startIndex, $endIndex);
    }

    /**
     * @param ParserBuilderInterface $parser
     * @param string                 $field      The field name
     * @param array                  $mapping    The field mapping
     * @param integer                $startIndex
     * @param integer                $endIndex
     */
    protected function addFieldModifiers(
        ParserBuilderInterface $parser,
        $field,
        array $mapping,
        $startIndex,
        $endIndex
    ) {
        // see if we need to translate it using foreign mapping
        $foreignMapping = $this->getForeignMapping();
        if (array_key_exists($field, $foreignMapping)) {
            $transformer = new ForeignMappingTransformer($field, $foreignMapping[$field]);
            $parser->addTransformerBetween($transformer, $field, $startIndex, $endIndex);
        }

        $this->addFieldTypeModifiers($parser, $field, $mapping, $startIndex, $endIndex);
    }

    /**
     * @param ParserBuilderInterface $parser
     * @param string                 $field      The field name
     * @param array                  $mapping    The field mapping
     * @param integer                $startIndex
     * @param integer                $endIndex
     */
    protected function addFieldTypeModifiers(ParserBuilderInterface $parser, $field, array $mapping, $startIndex, $endIndex)
    {
        // try to cast types
        switch ($mapping['type']) {
            case 'string':
                $parser->addTransformerBetween(new MultiLineToSingleLineTransformer(), $field, $startIndex, $endIndex);
                $parser->addTransformerBetween(new MultiSpaceToSingleSpaceTransformer(), $field, $startIndex, $endIndex);
                $parser->addTransformerBetween(new NormalizedStringTransformer(), $field, $startIndex, $endIndex);
                break;
            case 'text':
                $parser->addTransformerBetween(new MultiSpaceToSingleSpaceTransformer(), $field, $startIndex, $endIndex);
                $parser->addTransformerBetween(new NormalizedStringTransformer(), $field, $startIndex, $endIndex);
                break;

            case 'integer':
            case 'smallint':
                $parser->addTransformerBetween(
                    new LocalizedStringToNumberTransformer($this->options['number_locale'], 0, true, null),
                    $field,
                    $startIndex,
                    $endIndex
                );
                break;

            case 'decimal':
                $parser->addTransformerBetween(
                    new LocalizedStringToNumberTransformer($this->options['number_locale'], $mapping['scale'], true, null),
                    $field,
                    $startIndex,
                    $endIndex
                );
                break;

            case 'boolean':
                $parser->addTransformerBetween(
                    new StringToBooleanTransformer(['ja', 'j', 'y'], ['nee', 'n']),
                    $field,
                    $startIndex,
                    $endIndex
                );
                break;

            case 'date':
            case 'datetime':
                switch ($this->options['date_locale']) {
                    case 'nl':
                        $transformer = new DutchStringToDateTimeTransformer();
                        break;

                    default:
                        $transformer = new StringToDateTimeTransformer();

                        break;
                }

                $parser->addTransformerBetween($transformer, $field, $startIndex, $endIndex);
                $parser->addTransformerBetween(new DateTimeToIso8601Transformer(), $field, $startIndex, $endIndex);
                break;
        }

        if ($mapping['nullable']) {
            $parser->addTransformerBetween(new EmptyValueToNullTransformer(), $field, $startIndex, $endIndex);
        }
    }

    /**
     * @param ParserBuilderInterface $parser
     * @param integer                $startStartIndex
     * @param integer                $endIndex
     */
    protected function addFinalModifiers(ParserBuilderInterface $parser, $startStartIndex, $endIndex)
    {
        // set default values
        $parser->addModifierBetween(new DefaultValuesTransformer($this->options['default_values']), $startStartIndex, $endIndex);

        // scrub obsolete fields
        $parser->addModifierBetween(new ObsoleteFieldsTransformer($this->getMappedFields()), $startStartIndex, $endIndex);
    }

    /**
     * Returns the names of all mapped and extra mapped fields. These are the
     * fields that are allowed in the resulting item. The fields are
     * normalized to be lowercased and underscored (instead of dashes).
     *
     * @return array
     */
    protected function getMappedFields()
    {
        $fields = array_diff(
            array_unique(
                array_merge(
                    array_values($this->getEntityFields()),
                    array_values($this->getExtraMappedFields()),
                    array_keys($this->getMapping())
                )
            ),
            $this->getUnmappedFields()
        );

        $fields = new ParameterBag(array_flip($fields));

        // make sure id is not in it
        $fields->remove('id');

        $transformer = new UnderscoreKeysTransformer();
        $transformer->transform($fields);

        return $fields->keys();
    }

    /**
     * Specify fields here that are explicitly not mapped
     *
     * @return array
     */
    protected function getUnmappedFields()
    {
        return [];
    }

    /**
     * Specify fields here that are not mapped directly, but need to stay in the resulting item
     *
     * @return array
     */
    protected function getExtraMappedFields()
    {
        return [];
    }

    /**
     * @return string
     */
    protected function getOriginalIdSelector()
    {
    }

    /**
     * @return string
     */
    protected function getOriginalUrlSelector()
    {
    }

    /**
     * @return string
     */
    protected function getModificationDateSelector()
    {
    }

    /**
     * @return EntityManagerInterface
     */
    protected function getEntityManager()
    {
        return $this->doctrine->getManager();
    }
}
