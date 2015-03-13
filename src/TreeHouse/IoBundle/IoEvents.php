<?php

namespace TreeHouse\IoBundle;

final class IoEvents
{
    /**
     * Dispatched when feed cleanup is halted due to reached threshold
     */
    const FEED_CLEANUP_HALT       = 'io.cleanup.feed.halt';

    /**
     * Dispatched when feed cleanup is skipped due to incomplete import
     */
    const FEED_CLEANUP_SKIP       = 'io.cleanup.feed.skip';

    /**
     * Dispatched before a feed is cleaned up
     */
    const PRE_CLEAN_FEED          = 'io.cleanup.feed.pre';

    /**
     * Dispatched after a feed is cleaned up
     */
    const POST_CLEAN_FEED         = 'io.cleanup.feed.post';

    /**
     * Dispatched when source cleanup is halted due to reached threshold
     */
    const SOURCE_CLEANUP_HALT     = 'io.cleanup.source.halt';

    /**
     * Dispatched when all sources are cleaned
     */
    const SOURCE_CLEANUP_COMPLETE = 'io.cleanup.source.complete';

    /**
     * Dispatched before a source is cleaned up
     */
    const PRE_CLEAN_SOURCE        = 'io.cleanup.source.pre';

    /**
     * Dispatched after a source is cleaned up
     */
    const POST_CLEAN_SOURCE       = 'io.cleanup.source.post';

    /**
     * Dispatched when a source process can be triggered
     */
    const SOURCE_PROCESS          = 'io.source.process';

    /**
     * Dispatched when a source is processed
     */
    const SOURCE_PROCESSED        = 'io.source.processed';

    /**
     * Dispatched when a source is linked
     */
    const SOURCE_LINKED           = 'io.source.linked';
}
