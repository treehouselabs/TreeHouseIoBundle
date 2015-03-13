<?php

namespace TreeHouse\IoBundle\Import\Feed\Type;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Symfony\Component\HttpFoundation\ParameterBag;
use TreeHouse\Feeder\Modifier\Data\Transformer\DateTimeToIso8601Transformer;
use TreeHouse\Feeder\Modifier\Data\Transformer\EmptyValueToNullTransformer;
use TreeHouse\Feeder\Modifier\Data\Transformer\NodeToStringTransformer;
use TreeHouse\Feeder\Modifier\Data\Transformer\StringToBooleanTransformer;
use TreeHouse\Feeder\Modifier\Data\Transformer\TraversingTransformer;
use TreeHouse\Feeder\Modifier\Item\Mapper\PathMapper;
use TreeHouse\Feeder\Modifier\Item\Transformer\ObsoleteFieldsTransformer;
use TreeHouse\Feeder\Modifier\Item\Transformer\UnderscoreKeysTransformer;
use TreeHouse\IoBundle\Import\Feed\FeedBuilderInterface;
use TreeHouse\IoBundle\Item\Modifier\Data\Transformer\DutchStringToDateTimeTransformer;
use TreeHouse\IoBundle\Item\Modifier\Data\Transformer\EntityToIdTransformer;
use TreeHouse\IoBundle\Item\Modifier\Data\Transformer\ForeignMappingTransformer;
use TreeHouse\IoBundle\Item\Modifier\Data\Transformer\LocalizedStringToNumberTransformer;
use TreeHouse\IoBundle\Item\Modifier\Data\Transformer\MultiLineToSingleLineTransformer;
use TreeHouse\IoBundle\Item\Modifier\Data\Transformer\MultiSpaceToSingleSpaceTransformer;
use TreeHouse\IoBundle\Item\Modifier\Data\Transformer\NormalizedStringTransformer;
use TreeHouse\IoBundle\Item\Modifier\Data\Transformer\StringToDateTimeTransformer;
use TreeHouse\IoBundle\Item\Modifier\Item\Transformer\DefaultValuesTransformer;
use TreeHouse\IoBundle\Source\Manager\ImportSourceManager;

abstract class DefaultFeedType extends AbstractFeedType
{
    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    /**
     * @var array
     */
    protected $options;

    /**
     * @param ManagerRegistry     $doctrine
     * @param ImportSourceManager $sourceManager
     */
    public function __construct(ManagerRegistry $doctrine, ImportSourceManager $sourceManager)
    {
        $this->doctrine = $doctrine;

        parent::__construct($sourceManager);
    }

    /**
     * @return array
     */
    abstract public function getMapping();

    /**
     * @inheritdoc
     */
    public function build(FeedBuilderInterface $builder, array $options)
    {
        parent::build($builder, $options);

        $this->options = $options;

        // 2000-3000: map paths
        $builder->addModifier(new PathMapper($this->getMapping()), 2000);

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
     * Override this method to add custom modifiers to the feed
     *
     * @param FeedBuilderInterface $builder
     * @param array                $options
     */
    protected function addCustomModifiers(FeedBuilderInterface $builder, array $options)
    {
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
     * Specify a mapping here from foreign configuration to our configuration
     *
     * @return array
     */
    protected function getForeignMapping()
    {
        return [];
    }

    /**
     * Automatically adds modifiers based on entity field/association mapping
     *
     * @param FeedBuilderInterface $builder
     * @param integer              $startIndex
     * @param integer              $endIndex
     */
    protected function addEntityModifiers(FeedBuilderInterface $builder, $startIndex, $endIndex)
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
     * @param FeedBuilderInterface $builder
     * @param string               $association The association name
     * @param array                $mapping     The association mapping
     * @param integer              $startIndex
     * @param integer              $endIndex
     *
     * @return integer The updated index
     */
    protected function addAssociationModifiers(
        FeedBuilderInterface $builder,
        $association,
        array $mapping,
        $startIndex,
        $endIndex
    ) {
        $transformer = new EntityToIdTransformer($this->getEntityManager());

        if ($mapping['type'] & ClassMetadataInfo::TO_MANY) {
            $transformer = new TraversingTransformer($transformer);
        }

        $this->addTransformerBetween($builder, $transformer, $association, $startIndex, $endIndex);
    }

    /**
     * @param FeedBuilderInterface $builder
     * @param string               $field      The field name
     * @param array                $mapping    The field mapping
     * @param integer              $startIndex
     * @param integer              $endIndex
     */
    protected function addFieldModifiers(
        FeedBuilderInterface $builder,
        $field,
        array $mapping,
        $startIndex,
        $endIndex
    ) {
        // always transform node text values
        $this->addTransformerBetween($builder, new NodeToStringTransformer(), $field, $startIndex, $endIndex);

        // see if we need to translate it using foreign mapping
        $foreignMapping = $this->getForeignMapping();
        if (array_key_exists($field, $foreignMapping)) {
            $transformer = new ForeignMappingTransformer($field, $foreignMapping[$field]);
            $this->addTransformerBetween($builder, $transformer, $field, $startIndex, $endIndex);
        }

        $this->addFieldTypeModifiers($builder, $field, $mapping, $startIndex, $endIndex);
    }

    /**
     * @param FeedBuilderInterface $builder
     * @param string               $field      The field name
     * @param array                $mapping    The field mapping
     * @param integer              $startIndex
     * @param integer              $endIndex
     */
    protected function addFieldTypeModifiers(FeedBuilderInterface $builder, $field, array $mapping, $startIndex, $endIndex)
    {
        // try to cast types
        switch ($mapping['type']) {
            case 'string':
                $this->addTransformerBetween($builder, new MultiLineToSingleLineTransformer(), $field, $startIndex, $endIndex);
                $this->addTransformerBetween($builder, new MultiSpaceToSingleSpaceTransformer(), $field, $startIndex, $endIndex);
                $this->addTransformerBetween($builder, new NormalizedStringTransformer(), $field, $startIndex, $endIndex);
                break;
            case 'text':
                $this->addTransformerBetween($builder, new MultiSpaceToSingleSpaceTransformer(), $field, $startIndex, $endIndex);
                $this->addTransformerBetween($builder, new NormalizedStringTransformer(), $field, $startIndex, $endIndex);
                break;

            case 'integer':
            case 'smallint':
                $this->addTransformerBetween(
                    $builder,
                    new LocalizedStringToNumberTransformer(\NumberFormatter::TYPE_INT32, 0, true, null, $this->options['number_locale']),
                    $field,
                    $startIndex,
                    $endIndex
                );
                break;
            case 'decimal':
                $this->addTransformerBetween(
                    $builder,
                    new LocalizedStringToNumberTransformer(\NumberFormatter::TYPE_DOUBLE, 0, true, null, $this->options['number_locale']),
                    $field,
                    $startIndex,
                    $endIndex
                );
                break;

            case 'boolean':
                $this->addTransformerBetween(
                    $builder,
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

                $this->addTransformerBetween($builder, $transformer, $field, $startIndex, $endIndex);
                $this->addTransformerBetween($builder, new DateTimeToIso8601Transformer(), $field, $startIndex, $endIndex);
                break;
        }

        if ($mapping['nullable']) {
            $this->addTransformerBetween($builder, new EmptyValueToNullTransformer(), $field, $startIndex, $endIndex);
        }
    }

    /**
     * @param FeedBuilderInterface $builder
     * @param integer              $startStartIndex
     * @param integer              $endIndex
     *
     * @internal param int $index The index to start adding modifiers with
     */
    protected function addFinalModifiers(FeedBuilderInterface $builder, $startStartIndex, $endIndex)
    {
        // set default values
        $this->addModifierBetween($builder, new DefaultValuesTransformer($this->options['default_values']), $startStartIndex, $endIndex);

        // scrub obsolete fields
        $this->addModifierBetween($builder, new ObsoleteFieldsTransformer($this->getMappedFields()), $startStartIndex, $endIndex);
    }

    /**
     * @return EntityManagerInterface
     */
    protected function getEntityManager()
    {
        return $this->doctrine->getManager();
    }
}
