<?php

namespace TreeHouse\IoBundle\Tests\Export;

use Symfony\Component\Templating\EngineInterface;
use TreeHouse\IoBundle\Export\FeedType\FeedTypeInterface;
use TreeHouse\IoBundle\Export\FeedWriter;
use TreeHouse\IoBundle\Export\FeedWriterFactory;

class FeedWriterFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $factory = new FeedWriterFactory($this->getMockForAbstractClass(EngineInterface::class));

        $this->assertInstanceOf(FeedWriterFactory::class, $factory);
    }

    public function testCreateWriter()
    {
        $factory = new FeedWriterFactory($this->getMockForAbstractClass(EngineInterface::class));

        $writer = $factory->createWriter($this->getMockForAbstractClass(FeedTypeInterface::class));

        $this->assertInstanceOf(FeedWriter::class, $writer);
    }
}
