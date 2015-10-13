<?php

namespace TreeHouse\IoBundle\Source;

use TreeHouse\IoBundle\Exception\SourceLinkException;
use TreeHouse\IoBundle\Exception\SourceProcessException;
use TreeHouse\IoBundle\Model\SourceInterface;

/**
 * A source processor takes a source, processes its data and
 * creates or updates a resulting entity.
 */
interface SourceProcessorInterface
{
    /**
     * Links a source to an entity.
     *
     * @param SourceInterface $source
     *
     * @throws SourceLinkException When source could not be linked
     */
    public function link(SourceInterface $source);

    /**
     * Unlinks a source from an entity.
     *
     * @param SourceInterface $source
     *
     * @throws SourceLinkException When source could not be unlinked
     */
    public function unlink(SourceInterface $source);

    /**
     * Checks if a source is linked by the processor.
     *
     * @param SourceInterface $source
     *
     * @return bool
     */
    public function isLinked(SourceInterface $source);

    /**
     * Processes a source and its linked entity. Only linked sources can
     * be processed.
     *
     * @param SourceInterface $source
     *
     * @throws SourceProcessException When source could not be processed
     */
    public function process(SourceInterface $source);

    /**
     * Checks if a source is supported by the processor.
     *
     * @param SourceInterface $source
     *
     * @return bool
     */
    public function supports(SourceInterface $source);
}
