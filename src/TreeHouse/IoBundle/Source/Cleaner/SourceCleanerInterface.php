<?php

namespace TreeHouse\IoBundle\Source\Cleaner;

/**
 * A source cleaner is responsible for cleaning sources (duh).
 * Most of the time this will be removing the sources, but
 * depending on the implementation, this may vary.
 */
interface SourceCleanerInterface
{
    /**
     * @param DelegatingSourceCleaner $cleaner
     * @param ThresholdVoterInterface $voter
     *
     * @return int The number of cleaned sources
     */
    public function clean(DelegatingSourceCleaner $cleaner, ThresholdVoterInterface $voter);
}
