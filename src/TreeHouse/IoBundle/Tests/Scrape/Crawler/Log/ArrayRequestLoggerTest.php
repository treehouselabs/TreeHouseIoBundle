<?php

namespace TreeHouse\IoBundle\Tests\Scrape\Crawler\Log;

use TreeHouse\IoBundle\Scrape\Crawler\Log\ArrayRequestLogger;

class ArrayRequestLoggerTest extends AbstractRequestLoggerTest
{
    /**
     * @inheritdoc
     */
    protected function getLogger()
    {
        return new ArrayRequestLogger();
    }
}
