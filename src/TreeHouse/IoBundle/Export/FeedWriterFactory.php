<?php

namespace TreeHouse\IoBundle\Export;

use Symfony\Component\Templating\EngineInterface;
use TreeHouse\IoBundle\Export\FeedType\FeedTypeInterface;

class FeedWriterFactory
{
    /**
     * @var EngineInterface
     */
    protected $templating;

    /**
     * @param EngineInterface $templating
     */
    public function __construct(EngineInterface $templating)
    {
        $this->templating = $templating;
    }

    /**
     * @param FeedTypeInterface $type
     *
     * @return FeedWriter
     */
    public function createWriter(FeedTypeInterface $type)
    {
        return new FeedWriter($this->templating, $type->getRootNode(), $type->getItemNode());
    }
}
