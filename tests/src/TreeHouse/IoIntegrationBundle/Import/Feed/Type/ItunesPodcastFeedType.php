<?php

namespace TreeHouse\IoIntegrationBundle\Import\Feed\Type;

use Doctrine\Common\Inflector\Inflector;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Markdownify\ConverterExtra;
use TreeHouse\Feeder\Exception\FilterException;
use TreeHouse\Feeder\Exception\ModificationException;
use TreeHouse\Feeder\Exception\TransformationFailedException;
use TreeHouse\Feeder\Modifier\Data\Transformer\CallbackTransformer;
use TreeHouse\Feeder\Modifier\Item\Filter\CallbackFilter;
use TreeHouse\Feeder\Modifier\Item\Transformer\CallbackTransformer as CallbackItemTransformer;
use TreeHouse\IoBundle\Import\Feed\FeedBuilderInterface;
use TreeHouse\IoBundle\Import\Feed\FeedItemBag;
use TreeHouse\IoBundle\Import\Feed\Type\DefaultFeedType;
use TreeHouse\IoBundle\Item\Modifier\Data\Transformer\HtmlToMarkdownTransformer;
use TreeHouse\IoBundle\Item\Modifier\Data\Transformer\PurifiedHtmlTransformer;
use TreeHouse\IoIntegrationBundle\Entity\Author;

class ItunesPodcastFeedType extends DefaultFeedType
{
    /**
     * @inheritdoc
     */
    public function getItemName()
    {
        return 'item';
    }

    /**
     * @inheritdoc
     */
    protected function getOriginalIdField()
    {
        return 'guid';
    }

    /**
     * @inheritdoc
     */
    protected function getOriginalUrlField()
    {
        return 'link';
    }

    /**
     * @inheritdoc
     */
    protected function getModificationDateField()
    {
        // this isn't really a modification field but we use it anyway to test its functionality
        return 'pubdate';
    }

    /**
     * @inheritdoc
     */
    public function getMapping()
    {
        return [
            'title'          => 'title',
            'itunessummary'  => 'summary',
            'description'    => 'body',
            'pubdate'        => 'datetime_published',
            'itunesauthor'   => 'author',
            'itunesduration' => 'duration',
            'itunesimage'    => 'image_url',
            'enclosure'      => 'audio_url',
        ];
    }

    /**
     * @inheritdoc
     */
    public function addCustomModifiers(FeedBuilderInterface $builder, array $options)
    {
        $builder->addTransformer(
            new CallbackTransformer(function ($value) {
                return $value['@href'];
            }),
            'itunes:image',
            50
        );

        $builder->addTransformer(
            new CallbackTransformer(function ($value) {
                return $value['@url'];
            }),
            'enclosure',
            51
        );

        $this->addModifierBetween(
            $builder,
            new CallbackFilter(function (FeedItemBag $item) {
                if ($item->get('itunesexplicit') === 'yes') {
                    throw new FilterException('Explicit content');
                }
            }),
            2000,
            2500
        );

        $this->addTransformerBetween(
            $builder,
            new HtmlToMarkdownTransformer(new ConverterExtra(), new \HTMLPurifier($this->getPurifierConfig())),
            'body',
            3000,
            3500
        );

        $this->addTransformerBetween(
            $builder,
            new CallbackTransformer(function ($value) {
                if (strpos($value, ':') === false) {
                    throw new TransformationFailedException('Invalid duration');
                }

                list ($hours, $minutes, $seconds) = explode(':', $value);

                return (3600 * $hours) + (60 * $minutes) + $seconds;
            }),
            'duration',
            3000,
            3500
        );

        $this->addTransformerBetween(
            $builder,
            new CallbackTransformer(function ($value) {
                $repo = $this->doctrine->getRepository('TreeHouseIoIntegrationBundle:Author');
                if (null === $author = $repo->findOneBy(['name' => $value])) {
                    $author = new Author();
                    $author->setName($value);
                    $this->doctrine->getManager()->persist($author);
                    $this->doctrine->getManager()->flush($author);
                }

                return $author;
            }),
            'author',
            3000,
            3500
        );

        $this->addModifierBetween(
            $builder,
            new CallbackItemTransformer(function (FeedItemBag $item) {
                preg_match('~^(\d+):~', $item->get('title'), $matches);
                $item->set('number', $matches[1]);
            }),
            3000,
            3500
        );

    }

    /**
     * @inheritdoc
     */
    protected function getAssociationMapping($association)
    {
        $association = Inflector::camelize($association);
        $meta = $this->getEntityMetadata();

        if ($meta->hasAssociation($association)) {
            return $meta->getAssociationMapping($association);
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    protected function getFieldMapping($field)
    {
        $field = Inflector::camelize($field);
        $meta = $this->getEntityMetadata();

        if ($meta->hasField($field)) {
            return $meta->getFieldMapping($field);
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    protected function getEntityFields()
    {
        $meta = $this->getEntityMetadata();

        return array_merge($meta->getFieldNames(), $meta->getAssociationNames());
    }

    /**
     * @return ClassMetadata
     */
    protected function getEntityMetadata()
    {
        return $this->doctrine->getManager()->getClassMetadata('TreeHouseIoIntegrationBundle:Episode');
    }

    /**
     * @return array
     */
    protected function getPurifierConfig()
    {
        return [
            'Attr.AllowedClasses'                     => [],
            'AutoFormat.AutoParagraph'                => true,
            'AutoFormat.RemoveEmpty'                  => true,
            'AutoFormat.RemoveEmpty.RemoveNbsp'       => true,
            'AutoFormat.RemoveSpansWithoutAttributes' => true,
            'Core.RemoveProcessingInstructions'       => true,
            'Cache.SerializerPermissions'             => 0775,
            'HTML.Allowed'                            => 'div,p,span,br,em,strong,b,i,small,cite,blockquote,q,code,var,samp,kbd,dfn,abbr,sup,sub,h1,h2,h3,ul,li',
            'HTML.Doctype'                            => 'HTML 4.01 Strict',
        ];
    }
}
