<?php

namespace TreeHouse\IoBundle\Tests\Import;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use TreeHouse\IoBundle\Entity\Import;
use TreeHouse\IoBundle\Import\Handler\HandlerInterface;
use TreeHouse\IoBundle\Import\Importer\Importer;
use TreeHouse\IoBundle\Import\Importer\ImporterBuilder;
use TreeHouse\IoBundle\Import\Importer\Type\ImporterTypeInterface;

class ImporterBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ImporterBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $builder;

    protected function setUp()
    {
        $this->builder = new ImporterBuilder($this->getEventDispatcherMock());
    }

    public function testConstructor()
    {
        $this->assertInstanceOf(ImporterBuilder::class, $this->builder);
    }

    public function testBuild()
    {
        $type = $this->getImporterTypeMock();
        $import = new Import();
        $handler = $this->getHandlerMock();

        $importer = $this->builder->build($type, $import, $handler, []);

        $this->assertInstanceOf(Importer::class, $importer);
    }

    /**
     * @return EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getEventDispatcherMock()
    {
        return $this->getMockForAbstractClass(EventDispatcherInterface::class);
    }

    /**
     * @return ImporterTypeInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getImporterTypeMock()
    {
        $mock = $this
            ->getMockBuilder(ImporterTypeInterface::class)
            ->getMockForAbstractClass()
        ;

        return $mock;
    }

    /**
     * @return HandlerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getHandlerMock()
    {
        $mock = $this
            ->getMockBuilder(HandlerInterface::class)
            ->getMockForAbstractClass()
        ;

        return $mock;
    }
}
