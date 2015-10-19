<?php

namespace TreeHouse\IoBundle\Tests\Source\Processor;

use TreeHouse\IoBundle\Source\Processor\DelegatingSourceProcessor;
use TreeHouse\IoBundle\Source\SourceProcessorInterface;
use TreeHouse\IoBundle\Test\Mock\SourceMock;

class DelegatingSourceProcessorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DelegatingSourceProcessor
     */
    protected $delegate;

    /**
     * @var SourceProcessorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $processor1;

    /**
     * @var SourceProcessorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $processor2;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->delegate = new DelegatingSourceProcessor();

        $this->processor1 = $this
            ->getMockBuilder(SourceProcessorInterface::class)
            ->getMockForAbstractClass()
        ;

        $this->processor2 = $this
            ->getMockBuilder(SourceProcessorInterface::class)
            ->getMockForAbstractClass()
        ;

        $this->delegate->registerProcessor($this->processor1);
        $this->delegate->registerProcessor($this->processor2);
    }

    public function testRegisterProcessor()
    {
        $processors = [$this->processor1, $this->processor2];

        $this->assertEquals($processors, $this->delegate->getProcessors());
    }

    public function testSupports()
    {
        $this->processor1
            ->expects($this->any())
            ->method('supports')
            ->will($this->returnValue(true))
        ;

        $this->assertTrue($this->delegate->supports(new SourceMock(123)));
    }

    public function testNotSupports()
    {
        $this->processor1
            ->expects($this->any())
            ->method('supports')
            ->will($this->returnValue(false))
        ;

        $this->assertFalse($this->delegate->supports(new SourceMock(123)));
    }

    public function testAnySupports()
    {
        $this->processor1->expects($this->any())->method('supports')->will($this->returnValue(false));
        $this->processor2->expects($this->any())->method('supports')->will($this->returnValue(true));

        $this->assertTrue($this->delegate->supports(new SourceMock(123)));
    }

    public function testAnyNotLinked()
    {
        $this->processor1->expects($this->any())->method('supports')->will($this->returnValue(true));
        $this->processor1->expects($this->any())->method('isLinked')->will($this->returnValue(true));

        $this->processor2->expects($this->any())->method('supports')->will($this->returnValue(true));
        $this->processor2->expects($this->any())->method('isLinked')->will($this->returnValue(false));

        $this->assertFalse($this->delegate->isLinked(new SourceMock(123)));
    }

    /**
     * @expectedException        \TreeHouse\IoBundle\Exception\SourceLinkException
     * @expectedExceptionMessage Source is blocked
     */
    public function testLinkOnBlockedSource()
    {
        $source = new SourceMock(1234);
        $source->setBlocked(true);

        $this->delegate->link($source);
    }

    public function testLink()
    {
        $this->processor1->expects($this->any())->method('supports')->will($this->returnValue(true));
        $this->processor1->expects($this->once())->method('link');

        $this->processor2->expects($this->any())->method('supports')->will($this->returnValue(false));
        $this->processor2->expects($this->never())->method('link');

        $this->delegate->link(new SourceMock(123));
    }

    public function testUnlink()
    {
        $this->processor1->expects($this->any())->method('supports')->will($this->returnValue(true));
        $this->processor1->expects($this->once())->method('unlink');

        $this->processor2->expects($this->any())->method('supports')->will($this->returnValue(false));
        $this->processor2->expects($this->never())->method('unlink');

        $this->delegate->unlink(new SourceMock(123));
    }

    /**
     * @expectedException        \TreeHouse\IoBundle\Exception\SourceProcessException
     * @expectedExceptionMessage Source is blocked
     */
    public function testProcessOnBlockedSource()
    {
        $source = new SourceMock(1234);
        $source->setBlocked(true);

        $this->delegate->process($source);
    }

    public function testProcessOnUnlinkedSource()
    {
        $this->processor1->expects($this->any())->method('supports')->will($this->returnValue(true));
        $this->processor1->expects($this->any())->method('isLinked')->will($this->returnValue(false));
        $this->processor1->expects($this->never())->method('process');

        $this->processor2->expects($this->any())->method('supports')->will($this->returnValue(false));
        $this->processor2->expects($this->never())->method('process');

        $this->delegate->process(new SourceMock(1234));
    }

    public function testProcess()
    {
        $this->processor1->expects($this->any())->method('supports')->will($this->returnValue(true));
        $this->processor1->expects($this->any())->method('isLinked')->will($this->returnValue(true));
        $this->processor1->expects($this->once())->method('process');

        $this->processor2->expects($this->any())->method('supports')->will($this->returnValue(true));
        $this->processor2->expects($this->any())->method('isLinked')->will($this->returnValue(true));
        $this->processor2->expects($this->once())->method('process');

        $this->delegate->process(new SourceMock(123));
    }
}
