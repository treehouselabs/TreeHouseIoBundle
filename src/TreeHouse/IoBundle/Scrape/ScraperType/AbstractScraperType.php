<?php

namespace FM\IoBundle\Scrape\ScraperType;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use FM\CargoBundle\Normalizer\Slugifier;
use FM\ClassificationBundle\Normalizer\WhitespaceNormalizer;
use FM\Feeder\Modifier\Data\Transformer\DateTimeToIso8601Transformer;
use FM\Feeder\Modifier\Data\Transformer\EmptyValueToNullTransformer;
use FM\Feeder\Modifier\Data\Transformer\EnumeratedStringToArrayTransformer;
use FM\Feeder\Modifier\Data\Transformer\StringToBooleanTransformer;
use FM\Feeder\Modifier\Data\Transformer\TraversingTransformer;
use FM\Feeder\Modifier\Item\Transformer\CallbackTransformer;
use FM\Feeder\Modifier\Item\Transformer\ObsoleteFieldsTransformer;
use FM\Feeder\Modifier\Item\Transformer\UnderscoreKeysTransformer;
use FM\IoBundle\Import\Modifier\Data\Transformer\DutchStringToDateTimeTransformer;
use FM\IoBundle\Import\Modifier\Data\Transformer\EntityToIdTransformer;
use FM\IoBundle\Import\Modifier\Data\Transformer\ForeignMappingTransformer;
use FM\IoBundle\Import\Modifier\Data\Transformer\LocalizedStringToNumberTransformer;
use FM\IoBundle\Import\Modifier\Data\Transformer\MultiLineToSingleLineTransformer;
use FM\IoBundle\Import\Modifier\Data\Transformer\MultiSpaceToSingleSpaceTransformer;
use FM\IoBundle\Import\Modifier\Data\Transformer\NormalizedStringTransformer;
use FM\IoBundle\Import\Modifier\Data\Transformer\StringToDateTimeTransformer;
use FM\IoBundle\Import\Modifier\Item\Transformer\DefaultValuesTransformer;
use FM\IoBundle\Scrape\Model\ScrapedDataBag;
use FM\IoBundle\Scrape\Modifier\Item\Mapper\NodeMapper;
use FM\IoBundle\Scrape\ScraperBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\OptionsResolver\OptionsResolver;
use TreeHouse\Getgeo\Client;
use TreeHouse\Model\Config\Config;
use TreeHouse\Model\Config\ConfigValueGuesser;

abstract class AbstractScraperType implements ScraperTypeInterface
{
    /**
     * @var array
     */
    protected $options = [];

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public function setOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired([
            'date_locale',
            'number_locale',
            'default_values',
        ]);
        $resolver->setAllowedValues([
            'date_locale'   => ['en', 'nl'],
            'number_locale' => ['en', 'nl'],
        ]);
        $resolver->setAllowedTypes([
            'default_values' => 'array',
        ]);
        $resolver->setDefaults([
            'date_locale'    => 'en',
            'number_locale'  => 'en',
            'default_values' => [],
        ]);
    }

    /**
     * @return array
     */
    abstract public function getMapping();

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
     * @param ScraperBuilderInterface $builder
     * @param array                   $options
     */
    public function build(ScraperBuilderInterface $builder, array $options = [])
    {
        $this->options = $options;

        // 2000-3000: map paths
        $builder->addModifier(new NodeMapper($this->getMapping()), 2000);

        // 3000-3500: transform specific fields

        // 3500-4000: feed-type specific: reserved for field transformers before the regular transformers

        // 4000-5000: reserved for transformers added automatically based on entity field mapping
        $this->addEntityModifiers($builder, 4000, 5000);

        // 5000-6000: feed-type specific: reserved for field transformers after the regular transformers

        // 6000-7000: reserved for modifiers after all other modifiers are done
        $this->addFinalModifiers($builder, 6000, 7000);

        // give extending feed type a method for custom modifiers
        $this->addCustomModifiers($builder, $options);
    }

    /**
     * @inheritdoc
     */
    public function getOriginalIdCallback()
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

            return;
        };
    }

    /**
     * @inheritdoc
     */
    public function getOriginalUrlCallback()
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

            return;
        };
    }

    /**
     * @inheritdoc
     */
    public function getModificationDateCallback()
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

            return;
        };
    }

    /**
     * Override this method to add custom modifiers to the feed
     *
     * @param ScraperBuilderInterface $builder
     * @param array                   $options
     */
    protected function addCustomModifiers(ScraperBuilderInterface $builder, array $options)
    {
    }

    /**
     * Automatically adds modifiers based on entity field/association mapping
     *
     * @param ScraperBuilderInterface $builder
     * @param integer                 $startIndex
     * @param integer                 $endIndex
     */
    protected function addEntityModifiers(ScraperBuilderInterface $builder, $startIndex, $endIndex)
    {
        // skip id
        $mappedFields = array_filter($this->getMappedFields(), function ($value) {
                return $value !== 'id';
            });

        foreach ($mappedFields as $key) {
            // see if association is in meta
            if (null !== $mapping = $this->getAssociationMapping($key)) {
                $this->addAssociationModifiers($builder, $key, $mapping, $startIndex, $endIndex);
                continue;
            }

            // see if field is in meta
            if (null !== $mapping = $this->getFieldMapping($key)) {
                $this->addFieldModifiers($builder, $key, $mapping, $startIndex, $endIndex);
                continue;
            }
        }
    }

    /**
     * @param ScraperBuilderInterface $builder
     * @param string                  $association The association name
     * @param array                   $mapping     The association mapping
     * @param integer                 $startIndex
     * @param integer                 $endIndex
     *
     * @return integer The updated index
     */
    protected function addAssociationModifiers(
        ScraperBuilderInterface $builder,
        $association,
        array $mapping,
        $startIndex,
        $endIndex
    ) {
        $transformer = new EntityToIdTransformer($this->getEntityManager());

        if ($mapping['type'] & ClassMetadataInfo::TO_MANY) {
            $transformer = new TraversingTransformer($transformer);
        }

        $builder->addTransformerBetween($transformer, $association, $startIndex, $endIndex);
    }

    /**
     * @param ScraperBuilderInterface $builder
     * @param string                  $field      The field name
     * @param array                   $mapping    The field mapping
     * @param integer                 $startIndex
     * @param integer                 $endIndex
     */
    protected function addFieldModifiers(
        ScraperBuilderInterface $builder,
        $field,
        array $mapping,
        $startIndex,
        $endIndex
    ) {
        // see if we need to translate it using foreign mapping
        $foreignMapping = $this->getForeignMapping();
        if (array_key_exists($field, $foreignMapping)) {
            $transformer = new ForeignMappingTransformer($field, $foreignMapping[$field]);
            $builder->addTransformerBetween($transformer, $field, $startIndex, $endIndex);
        }

        $this->addFieldTypeModifiers($builder, $field, $mapping, $startIndex, $endIndex);
    }

    /**
     * @param ScraperBuilderInterface $builder
     * @param string                  $field      The field name
     * @param array                   $mapping    The field mapping
     * @param integer                 $startIndex
     * @param integer                 $endIndex
     */
    protected function addFieldTypeModifiers(ScraperBuilderInterface $builder, $field, array $mapping, $startIndex, $endIndex)
    {
        $config  = $this->getModelConfig();
        $guesser = $this->getConfigValueGuesser();

        // see if we can guess value, add modifiers with offset 5 to place them at the right position
        // (see parent method for details)
        if ($guesser->supports($field)) {
            // if this can have multiple values, support string-enumerations
            if ($config->isMultiValued($field)) {
                $builder->addTransformerBetween(
                    new EnumeratedStringToArrayTransformer([',', '/', '+', 'en']),
                    $field,
                    $startIndex,
                    $endIndex
                );
            }

            $builder->addTransformerBetween(
                new StringToConfigValueTransformer($field, $guesser, $config),
                $field,
                $startIndex,
                $endIndex
            );
        }

        // try to cast types
        switch ($mapping['type']) {
            case 'string':
                $builder->addModifierBetween(new CallbackTransformer(
                    function (ScrapedDataBag $item) use ($field) {
                        if ($item->has($field)) {
                            $normalizer = new WhitespaceNormalizer();
                            $normalized = $normalizer->normalize($item->get($field));

                            $item->set($field, $normalized);
                        }
                    }
                ), $startIndex, $endIndex);
                $builder->addTransformerBetween(new MultiLineToSingleLineTransformer(), $field, $startIndex, $endIndex);
                $builder->addTransformerBetween(new MultiSpaceToSingleSpaceTransformer(), $field, $startIndex, $endIndex);
                $builder->addTransformerBetween(new NormalizedStringTransformer(), $field, $startIndex, $endIndex);
                break;
            case 'text':
                $builder->addTransformerBetween(new MultiSpaceToSingleSpaceTransformer(), $field, $startIndex, $endIndex);
                $builder->addTransformerBetween(new NormalizedStringTransformer(), $field, $startIndex, $endIndex);
                break;

            case 'integer':
            case 'smallint':
            case 'decimal':
            $builder->addTransformerBetween(
                    new LocalizedStringToNumberTransformer(\NumberFormatter::TYPE_DOUBLE, true, null, $this->options['number_locale']),
                    $field,
                    $startIndex,
                    $endIndex
                );
                break;

            case 'boolean':
                $builder->addTransformerBetween(
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

                $builder->addTransformerBetween($transformer, $field, $startIndex, $endIndex);
                $builder->addTransformerBetween(new DateTimeToIso8601Transformer(), $field, $startIndex, $endIndex);
                break;
        }

        if ($mapping['nullable']) {
            $builder->addTransformerBetween(new EmptyValueToNullTransformer(), $field, $startIndex, $endIndex);
        }
    }

    /**
     * @param ScraperBuilderInterface $builder
     * @param integer                 $startStartIndex
     * @param integer                 $endIndex
     *
     * @internal param int $index The index to start adding modifiers with
     */
    protected function addFinalModifiers(ScraperBuilderInterface $builder, $startStartIndex, $endIndex)
    {
        // set default values
        $builder->addModifierBetween(new DefaultValuesTransformer($this->options['default_values']), $startStartIndex, $endIndex);

        // scrub obsolete fields
        $builder->addModifierBetween(new ObsoleteFieldsTransformer($this->getMappedFields()), $startStartIndex, $endIndex);
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
                    array_values($this->getMapping())
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
     * @return Client
     */
    protected function getGetgeoClient()
    {
        return $this->container->get('fm_cargo.getgeo.client');
    }

    /**
     * @return Slugifier
     */
    protected function getSlugifier()
    {
        return $this->container->get("fm_cargo.slugifier");
    }

    /**
     * @return EntityManagerInterface
     */
    protected function getEntityManager()
    {
        return $this->container->get('doctrine')->getManager();
    }

    /**
     * @return ConfigValueGuesser
     */
    protected function getConfigValueGuesser()
    {
        return $this->container->get('fm_cargo.model.config.value_guesser');
    }

    /**
     * @return Config
     */
    protected function getModelConfig()
    {
        return $this->container->get('fm_cargo.model.config');
    }
}
