<?php

namespace TreeHouse\IoBundle\Tests\Scrape\Crawler\RateLimit;

use TreeHouse\IoBundle\Scrape\Crawler\RateLimit\EnablingTrait;

class EnablingTraitTest extends \PHPUnit_Framework_TestCase
{
    public function testEnable()
    {
        $limit = new EnablingTraitImpl();
        $limit->enable();

        $this->assertTrue($limit->isEnabled());
    }

    public function testDisable()
    {
        $limit = new EnablingTraitImpl();
        $limit->disable();

        $this->assertFalse($limit->isEnabled());
    }
}

class EnablingTraitImpl
{
    use EnablingTrait;
}
