<?php

namespace TreeHouse\IoBundle\Source\Processor;

use TreeHouse\IoBundle\Exception\SourceLinkException;
use TreeHouse\IoBundle\Exception\SourceProcessException;
use TreeHouse\IoBundle\Model\SourceInterface;
use TreeHouse\IoBundle\Source\SourceProcessorInterface;

/**
 * Keeps a collection of source processors and delegates to the processors
 * that support the given source.
 *
 * You can add your own processors by tagging your service with the
 * "io.source_processor" tag.
 */
class DelegatingSourceProcessor implements SourceProcessorInterface
{
    /**
     * @var SourceProcessorInterface[]
     */
    protected $processors = [];

    /**
     * Registers a processor
     *
     * @param SourceProcessorInterface $processor
     */
    public function registerProcessor(SourceProcessorInterface $processor)
    {
        $this->processors[] = $processor;
    }

    /**
     * @return SourceProcessorInterface[]
     */
    public function getProcessors()
    {
        return $this->processors;
    }

    /**
     * Checks with all registered processors for the source if it is linked
     *
     * @param  SourceInterface $source
     * @return boolean
     */
    public function isLinked(SourceInterface $source)
    {
        foreach ($this->processors as $processor) {
            if ($processor->supports($source) && !$processor->isLinked($source)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function link(SourceInterface $source)
    {
        if ($source->isBlocked()) {
            throw new SourceLinkException('Source is blocked and should not be linked');
        }

        foreach ($this->processors as $processor) {
            if ($processor->supports($source)) {
                $processor->link($source);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function unlink(SourceInterface $source)
    {
        foreach ($this->processors as $processor) {
            if ($processor->supports($source)) {
                $processor->unlink($source);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function process(SourceInterface $source)
    {
        if ($source->isBlocked()) {
            throw new SourceProcessException('Source is blocked and should not be processed');
        }

        foreach ($this->processors as $processor) {
            if ($processor->supports($source) && $processor->isLinked($source)) {
                $processor->process($source);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function supports(SourceInterface $source)
    {
        foreach ($this->processors as $processor) {
            if ($processor->supports($source)) {
                return true;
            }
        }

        return false;
    }
}
