<?php

namespace TreeHouse\IoBundle\Import;

final class ImportEvents
{
    // import start/end
    const IMPORT_START = 'io.import.start';
    const IMPORT_FINISH = 'io.import.finish';

    // import rotated
    const IMPORT_ROTATE = 'io.import.rotate';

    // part creation/scheduling
    const PART_CREATED = 'io.import.part.created';
    const PART_SCHEDULED = 'io.import.part.scheduled';

    // part start/finish
    const PART_START = 'io.import.part.start';
    const PART_FINISH = 'io.import.part.finish';

    // item start/end
    const ITEM_START = 'io.import.item.start';
    const ITEM_FINISH = 'io.import.item.finish';

    // after item is mapped
    const ITEM_MAPPED = 'io.import.item.mapped';

    // various item import outcomes
    const ITEM_SUCCESS = 'io.import.item.success';
    const ITEM_FAILED = 'io.import.item.failed';
    const ITEM_SKIPPED = 'io.import.item.skipped';
    const ITEM_HANDLED = 'io.import.item.handled';

    // when batch is completed
    const BATCH_PRE_COMPLETE = 'io.import.batch.pre_complete';
    const BATCH_POST_COMPLETE = 'io.import.batch.post_complete';

    // when exception is thrown
    const EXCEPTION = 'io.import.exception';
}
