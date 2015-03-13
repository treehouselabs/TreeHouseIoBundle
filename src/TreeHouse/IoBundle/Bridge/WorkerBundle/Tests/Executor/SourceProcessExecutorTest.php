<?php

namespace TreeHouse\IoBundle\Bridge\WorkerBundle\Tests\Executor;

use Psr\Log\LogLevel;
use Psr\Log\NullLogger;
use TreeHouse\IoBundle\Exception\SourceLinkException;
use TreeHouse\IoBundle\Exception\SourceProcessException;
use TreeHouse\IoBundle\Bridge\WorkerBundle\Executor\SourceProcessExecutor;
use TreeHouse\IoBundle\Source\Processor\DelegatingSourceProcessor;
use TreeHouse\IoBundle\Source\SourceManagerInterface;
use TreeHouse\IoBundle\Tests\Mock\SourceMock;

class SourceProcessExecutorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SourceManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $manager;

    /**
     * @var DelegatingSourceProcessor|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $processor;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->manager = $this
            ->getMockBuilder(SourceManagerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['findById'])
            ->getMockForAbstractClass()
        ;

        $this->processor = $this
            ->getMockBuilder(DelegatingSourceProcessor::class)
            ->disableOriginalConstructor()
            ->setMethods(['link', 'unlink', 'isLinked', 'process'])
            ->getMock()
        ;
    }

    public function testLinkFirst()
    {
        $executor = new SourceProcessExecutor($this->manager, $this->processor, new NullLogger());

        $source = new SourceMock(12345);

        $this->manager->expects($this->once())->method('findById')->will($this->returnValue($source));
        $this->processor->expects($this->once())->method('isLinked')->will($this->returnValue(false));
        $this->processor->expects($this->once())->method('link')->will($this->returnValue(true));
        $this->manager->expects($this->once())->method('flush')->with($source);

        $executor->execute($executor->getObjectPayload($source));
    }

    public function testLinkException()
    {
        $executor = new SourceProcessExecutor($this->manager, $this->processor, new NullLogger());

        $source = new SourceMock(12345);

        $this->manager->expects($this->once())->method('findById')->will($this->returnValue($source));
        $this->processor->expects($this->once())->method('isLinked')->will($this->returnValue(false));

        $this->processor
            ->expects($this->once())
            ->method('link')
            ->will($this->throwException(new SourceLinkException('Foobar')))
        ;

        $this->assertFalse($executor->execute($executor->getObjectPayload($source)));

        $messages = $source->getMessages();
        $this->assertInternalType('array', $messages);
        $this->assertArrayHasKey('link', $messages);
        $this->assertArrayHasKey(LogLevel::ERROR, $messages['link']);
        $this->assertContains('Foobar', $messages['link'][LogLevel::ERROR]);
    }

    public function testExecute()
    {
        $executor = new SourceProcessExecutor($this->manager, $this->processor, new NullLogger());

        $source = new SourceMock(12345);

        $this->manager->expects($this->once())->method('findById')->will($this->returnValue($source));
        $this->processor->expects($this->once())->method('isLinked')->will($this->returnValue(true));
        $this->processor->expects($this->once())->method('process')->will($this->returnValue(true));

        $this->assertTrue($executor->execute($executor->getObjectPayload($source)));
    }

    public function testCannotFindSource()
    {
        $executor = new SourceProcessExecutor($this->manager, $this->processor, new NullLogger());

        $source = new SourceMock(12345);

        $this->manager->expects($this->once())->method('findById')->will($this->returnValue(null));
        $this->processor->expects($this->never())->method('link')->will($this->returnValue(true));

        $this->assertFalse($executor->execute([$executor->getObjectPayload($source)]));
    }

    public function testBlockedSource()
    {
        $executor = new SourceProcessExecutor($this->manager, $this->processor, new NullLogger());

        $source = new SourceMock(12345);
        $source->setBlocked(true);

        $this->manager->expects($this->once())->method('findById')->will($this->returnValue($source));
        $this->processor->expects($this->never())->method('link')->will($this->returnValue(true));
        $this->processor->expects($this->once())->method('unlink')->will($this->returnValue(true));

        $this->assertFalse($executor->execute([$executor->getObjectPayload($source)]));
    }

    public function testProcessException()
    {
        $executor = new SourceProcessExecutor($this->manager, $this->processor, new NullLogger());

        $source = new SourceMock(12345);

        $this->manager->expects($this->once())->method('findById')->will($this->returnValue($source));
        $this->processor->expects($this->once())->method('isLinked')->will($this->returnValue(false));

        $this->processor
            ->expects($this->once())
            ->method('process')
            ->will($this->throwException(new SourceProcessException('Foobar')))
        ;

        $this->assertFalse($executor->execute($executor->getObjectPayload($source)));

        $messages = $source->getMessages();
        $this->assertInternalType('array', $messages);
        $this->assertArrayHasKey('process', $messages);
        $this->assertArrayHasKey(LogLevel::ERROR, $messages['process']);
        $this->assertContains('Foobar', $messages['process'][LogLevel::ERROR]);
    }
}
