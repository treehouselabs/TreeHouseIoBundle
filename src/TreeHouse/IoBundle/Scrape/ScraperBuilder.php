<?php

namespace FM\IoBundle\Scrape;

use FM\Feeder\Modifier\Data\Transformer\TransformerInterface;
use FM\Feeder\Modifier\Item\Filter\FilterInterface;
use FM\Feeder\Modifier\Item\ModifierInterface;
use FM\Feeder\Modifier\Item\Transformer\DataTransformer;
use FM\Feeder\Modifier\Item\Validator\ValidatorInterface;
use FM\IoBundle\Scrape\ScraperType\ScraperTypeInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ScraperBuilder implements ScraperBuilderInterface
{
    /**
     * @var ModifierInterface[]
     */
    protected $modifiers = [];

    /**
     * List of scraper types, stored by hostname(s)
     *
     * @var ScraperTypeInterface[]
     */
    protected $scraperTypes = [];

    /**
     * @var Scraper[]
     */
    protected $scrapers = [];

    /**
     * @inheritdoc
     */
    public function addScraperType(ScraperTypeInterface $scraperTypeInterface)
    {
        $this->scraperTypes[] = $scraperTypeInterface;
    }

    /**
     * @inheritdoc
     */
    public function addModifier(ModifierInterface $modifier, $position = null, $continue = null)
    {
        if (null === $position) {
            $position = sizeof($this->modifiers) ? (max(array_keys($this->modifiers)) + 1) : 0;
        }

        if (null === $continue) {
            $continue = (!$modifier instanceof FilterInterface) && (!$modifier instanceof ValidatorInterface);
        }

        if (array_key_exists($position, $this->modifiers)) {
            throw new \InvalidArgumentException(sprintf('There already is a modifier at position %d', $position));
        }

        $this->modifiers[$position] = [$modifier, $continue];
    }

    /**
     * @inheritdoc
     */
    public function addModifierBetween(ModifierInterface $modifier, $startIndex, $endIndex, $continue = null)
    {
        for ($position = $startIndex; $position <= $endIndex; $position++) {
            if (!$this->hasModifierAt($position)) {
                $this->addModifier($modifier, $position, $continue);

                return;
            }
        }

        throw new \OutOfBoundsException(sprintf('No position left between %d and %d', $startIndex, $endIndex));
    }

    /**
     * @inheritdoc
     */
    public function addTransformerBetween(
        TransformerInterface $transformer,
        $field,
        $startIndex,
        $endIndex,
        $continue = null
    ) {
        $this->addModifierBetween(
            new DataTransformer($transformer, $field),
            $startIndex,
            $endIndex,
            $continue
        );
    }

    /**
     * @inheritdoc
     */
    public function getModifiers()
    {
        return $this->modifiers;
    }

    /**
     * @inheritdoc
     */
    public function addTransformer(TransformerInterface $transformer, $field, $position = null, $continue = true)
    {
        $this->addModifier(new DataTransformer($transformer, $field), $position, $continue);
    }

    /**
     * @inheritdoc
     */
    public function hasModifierAt($position)
    {
        return array_key_exists($position, $this->modifiers);
    }

    /**
     * @inheritdoc
     */
    public function removeModifier(ModifierInterface $modifier)
    {
        foreach ($this->modifiers as $position => list($mod,)) {
            if ($mod === $modifier) {
                unset($this->modifiers[$position]);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function removeModifierAt($position)
    {
        if (!array_key_exists($position, $this->modifiers)) {
            throw new \OutOfBoundsException(sprintf('There is no modifier at position %d', $position));
        }

        unset($this->modifiers[$position]);
    }

    /**
     * @inheritdoc
     */
    public function getScraperTypeSupportingUrl($url)
    {
        foreach ($this->scraperTypes as $scraperType) {
            if ($scraperType->supports($url)) {
                return $scraperType;
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function build($url, array $options = [])
    {
        $type = $this->getScraperTypeSupportingUrl($url);
        if (!$type) {
            throw new \InvalidArgumentException(sprintf(
                "Could not find a scraper type for url: '%s', %d types available!",
                $url,
                count($this->scraperTypes)
            ));
        }

        // try the scraper cache
        $typeClass = get_class($type);
        if (isset($this->scrapers[$typeClass])) {
            // found the scraper in the cache
            $scraper = $this->scrapers[$typeClass];
        } else {
            // build the scraper
            $resolver = $this->getOptionsResolver($type);

            $type->build($this, $resolver->resolve($options));

            $modifiers = $this->getModifiers();
            ksort($modifiers);

            $scraper = new Scraper();
            foreach ($modifiers as list($modifier, $continue)) {
                $scraper->addModifier($modifier, $continue);
            }

            // cache scrapers by class name
            $this->scrapers[$typeClass] = $scraper;

            // clear modifiers (they are only relevant to the type just build
            $this->modifiers = [];
        }

        return $scraper;
    }

    /**
     * @param ScraperTypeInterface $type
     *
     * @return OptionsResolver
     */
    protected function getOptionsResolver(ScraperTypeInterface $type)
    {
        $resolver = new OptionsResolver();
        $type->setOptions($resolver);

        return $resolver;
    }
}
