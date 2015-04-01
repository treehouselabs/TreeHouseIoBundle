<?php

namespace TreeHouse\IoBundle\Import\Feed\Type;

use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\OptionsResolver\OptionsResolver;
use TreeHouse\Feeder\Modifier\Data\Transformer\TransformerInterface;
use TreeHouse\Feeder\Modifier\Item\ModifierInterface;
use TreeHouse\Feeder\Modifier\Item\Transformer as FeederTransformer;
use TreeHouse\IoBundle\Entity\Feed;
use TreeHouse\IoBundle\Import\Feed\FeedBuilderInterface;
use TreeHouse\IoBundle\Item\Modifier\Item\Filter\BlockedSourceFilter;
use TreeHouse\IoBundle\Item\Modifier\Item\Filter\ModifiedItemFilter;
use TreeHouse\IoBundle\Item\Modifier\Item\Mapper\FeedItemBagMapper;
use TreeHouse\IoBundle\Item\Modifier\Item\Transformer as IoTransformer;
use TreeHouse\IoBundle\Item\Modifier\Item\Validator\OriginIdValidator;
use TreeHouse\IoBundle\Source\Manager\ImportSourceManager;

abstract class AbstractFeedType implements FeedTypeInterface
{
    /**
     * @var ImportSourceManager
     */
    protected $sourceManager;

    /**
     * @param ImportSourceManager $sourceManager
     */
    public function __construct(ImportSourceManager $sourceManager)
    {
        $this->sourceManager = $sourceManager;
    }

    /**
     * @inheritdoc
     */
    public function setOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired([
            'forced',
            'feed',
            'date_locale',
            'number_locale',
            'default_values',
        ]);

        $resolver->setAllowedValues('date_locale', ['en', 'nl']);
        $resolver->setAllowedValues('number_locale', ['en', 'nl']);

        $resolver->setAllowedTypes('forced', 'bool');
        $resolver->setAllowedTypes('feed', Feed::class);
        $resolver->setAllowedTypes('default_values', 'array');

        $resolver->setDefaults([
            'forced'         => false,
            'date_locale'    => 'en',
            'number_locale'  => 'en',
            'default_values' => [],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function build(FeedBuilderInterface $builder, array $options)
    {
        $sourceManager = $this->getSourceManager();

        // range 100-200: first make sure we have consistent keys and a valid origin
        $this->addModifierBetween($builder, new FeederTransformer\LowercaseKeysTransformer(), 100, 200);
        $this->addModifierBetween($builder, new FeederTransformer\UnderscoreKeysTransformer(), 100, 200);
        $this->addModifierBetween($builder, new FeederTransformer\StripKeysPunctuationTransformer(), 100, 200);
        $this->addModifierBetween($builder, new FeederTransformer\ExpandAttributesTransformer(), 100, 200);
        $this->addModifierBetween($builder, new FeederTransformer\TrimTransformer(), 100, 200);
        $this->addModifierBetween($builder, new FeederTransformer\StripCommentsTransformer(), 100, 200);

        // This transforms the regular ItemBag into our own FeedItemBag,
        // which adds logic for the original id/url and modification date.
        // We need to do this early on, since some of our filter/transformation
        // listeners depend on this.
        $mapper = new FeedItemBagMapper(
            $options['feed'],
            $this->getOriginalIdCallback(),
            $this->getOriginalUrlCallback(),
            $this->getModificationDateCallback()
        );
        $this->addModifierBetween($builder, $mapper, 200, 250);

        // range 300-400: perform validation and checks for skipping early on

        // validate origin id
        $this->addModifierBetween($builder, new OriginIdValidator(), 300, 400);

        // skip blocked sources
        $this->addModifierBetween($builder, new BlockedSourceFilter($sourceManager), 300, 400);

        // check for modification dates, but only when not forced
        if ($options['forced'] === false) {
            $this->addModifierBetween($builder, new ModifiedItemFilter($sourceManager), 300, 400);
        }
    }

    /**
     * @inheritdoc
     */
    public function getOriginalIdCallback()
    {
        return function (ParameterBag $item) {
            return $item->get($this->getOriginalIdField(), null, true);
        };
    }

    /**
     * @inheritdoc
     */
    public function getOriginalUrlCallback()
    {
        return function (ParameterBag $item) {
            return $item->get($this->getOriginalUrlField(), null, true);
        };
    }

    /**
     * @inheritdoc
     */
    public function getModificationDateCallback()
    {
        return function (ParameterBag $item) {
            if ($date = $item->get($this->getModificationDateField(), null, true)) {
                return new \DateTime($date);
            }

            return null;
        };
    }

    /**
     * Adds the given modifier between the start and end index, if there is a vacant position
     *
     * @param FeedBuilderInterface $builder
     * @param ModifierInterface    $modifier
     * @param integer              $startIndex
     * @param integer              $endIndex
     * @param boolean              $continue
     *
     * @throws \OutOfBoundsException
     */
    protected function addModifierBetween(
        FeedBuilderInterface $builder,
        ModifierInterface $modifier,
        $startIndex,
        $endIndex,
        $continue = null
    ) {
        for ($position = $startIndex; $position <= $endIndex; $position++) {
            if (!$builder->hasModifierAt($position)) {
                $builder->addModifier($modifier, $position, $continue);

                return;
            }
        }

        throw new \OutOfBoundsException(sprintf('No position left between %d and %d', $startIndex, $endIndex));
    }

    /**
     * Adds the given transformer between the start and end index, if there is a vacant position
     *
     * @param FeedBuilderInterface $builder
     * @param TransformerInterface $transformer
     * @param string               $field
     * @param integer              $startIndex
     * @param integer              $endIndex
     * @param boolean              $continue
     *
     * @throws \OutOfBoundsException
     */
    protected function addTransformerBetween(
        FeedBuilderInterface $builder,
        TransformerInterface $transformer,
        $field,
        $startIndex,
        $endIndex,
        $continue = null
    ) {
        $this->addModifierBetween(
            $builder,
            new FeederTransformer\DataTransformer($transformer, $field),
            $startIndex,
            $endIndex,
            $continue
        );
    }

    /**
     * @return ImportSourceManager
     */
    protected function getSourceManager()
    {
        return $this->sourceManager;
    }

    /**
     * @return string
     */
    abstract protected function getOriginalIdField();

    /**
     * @return string
     */
    abstract protected function getOriginalUrlField();

    /**
     * @return string
     */
    abstract protected function getModificationDateField();
}
