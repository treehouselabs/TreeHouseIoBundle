<?php

namespace TreeHouse\IoBundle\Export;

final class ExportEvents
{
    /**
     * Dispatched before an export feed is created
     */
    const PRE_EXPORT_FEED  = 'io.export.feed.pre';

    /**
     * Dispatched after an export feed is created
     */
    const POST_EXPORT_FEED = 'io.export.feed.post';

    /**
     * Dispatched before an export item is exported
     */
    const PRE_EXPORT_ITEM  = 'io.export.item.pre';

    /**
     * Dispatched after an export item is exported
     */
    const POST_EXPORT_ITEM = 'io.export.item.post';
}
