<?php

namespace TreeHouse\IoBundle\Tests\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use TreeHouse\IoBundle\EventListener\SourceModificationListener;
use TreeHouse\IoBundle\IoEvents;
use TreeHouse\IoBundle\Source\Processor\DelegatingSourceProcessor;
use TreeHouse\IoBundle\Tests\Mock\SourceMock;

class SourceModificationListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DelegatingSourceProcessor|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $sourceProcessor;

    /**
     * @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventDispatcher;

    /**
     * @var EntityManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityManager;

    /**
     * @var UnitOfWork|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $uow;

    /**
     * @var SourceModificationListener|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $listener;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->sourceProcessor = $this
            ->getMockBuilder(DelegatingSourceProcessor::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->eventDispatcher = $this
            ->getMockBuilder(EventDispatcherInterface::class)
            ->getMockForAbstractClass()
        ;

        $this->uow = $this
            ->getMockBuilder(UnitOfWork::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getEntityChangeSet',
                'getScheduledEntityInsertions',
                'getScheduledEntityUpdates',
                'getScheduledEntityDeletions',
            ])
            ->getMock()
        ;

        $this->entityManager = $this
            ->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['getUnitOfWork'])
            ->getMock()
        ;

        $this->entityManager
            ->expects($this->any())
            ->method('getUnitOfWork')
            ->will($this->returnValue($this->uow))
        ;

        $this->listener = $this
            ->getMockBuilder(SourceModificationListener::class)
            ->setConstructorArgs([$this->sourceProcessor, $this->eventDispatcher])
            ->setMethods([
                'isSourceModified',
                'isSourceLinked',
                'setSourceModificationDate',
                'scheduleSourceProcess',
            ])
            ->getMock()
        ;
    }

    /**
     * Returns Doctrine flush event with mocked EntityManager/UnitOfWork which returns the given entity.
     *
     * @param SourceMock $entity
     * @param string     $method
     *
     * @return OnFlushEventArgs
     */
    public function getOnFlushEventArgs($entity, $method = 'getScheduledEntityUpdates')
    {
        $methods = ['getScheduledEntityInsertions', 'getScheduledEntityUpdates', 'getScheduledEntityDeletions'];

        foreach ($methods as $test) {
            $this->uow
                ->expects($this->any())
                ->method($test)
                ->will($this->returnValue($method === $test ? [$entity] : []))
            ;
        }

        return new OnFlushEventArgs($this->entityManager);
    }

    public function testSourceNotModified()
    {
        $changeset = [
            'datetimeLastVisited' => [
                null,
                new \DateTime(),
            ],
        ];

        $this->uow
             ->expects($this->once())
             ->method('getEntityChangeSet')
             ->will($this->returnValue($changeset))
        ;

        $listener = new SourceModificationListenerMock($this->sourceProcessor, $this->eventDispatcher);

        $this->assertFalse($listener->visibleIsSourceModified(new SourceMock(12345), $this->uow));
    }

    public function testSourceModified()
    {
        $changeset = [
            'datetimeLastVisited' => [
                null,
                new \DateTime(),
            ],
            'data' => [
                null,
                ['foo' => 'bar'],
            ],
        ];

        $this->uow
             ->expects($this->once())
             ->method('getEntityChangeSet')
             ->will($this->returnValue($changeset))
        ;

        $listener = new SourceModificationListenerMock($this->sourceProcessor, $this->eventDispatcher);

        $this->assertTrue($listener->visibleIsSourceModified(new SourceMock(12345), $this->uow));
    }

    /**
     * Tests that the modification datetime is updated when the source is modified.
     */
    public function testSetModifiedDate()
    {
        $this->listener->expects($this->once())->method('isSourceModified')->will($this->returnValue(true));
        $this->listener->expects($this->once())->method('setSourceModificationDate');
        $this->listener->onFlush($this->getOnFlushEventArgs(new SourceMock(12345)));
    }

    /**
     * Tests that the modification datetime is *not* updated when the source is only visited.
     */
    public function testDontSetModifiedDate()
    {
        $this->listener->expects($this->once())->method('isSourceModified')->will($this->returnValue(false));
        $this->listener->expects($this->never())->method('setSourceModificationDate');
        $this->listener->onFlush($this->getOnFlushEventArgs(new SourceMock(12345)));
    }

    /**
     * Tests that a source is scheduled for processing if it's modified.
     */
    public function testScheduleSourceProcessOnSourceModified()
    {
        $this->listener->expects($this->once())->method('isSourceModified')->will($this->returnValue(true));
        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->equalTo(IoEvents::SOURCE_PROCESS))
        ;

        $this->listener->onFlush($this->getOnFlushEventArgs(new SourceMock(12345)));
        $this->listener->postFlush(new PostFlushEventArgs($this->entityManager));
    }

    /**
     * Tests that a source is scheduled for processing if it's unlinked.
     */
    public function testScheduleSourceProcessOnSourceUnlinked()
    {
        $this->listener->expects($this->once())->method('isSourceModified')->will($this->returnValue(false));
        $this->sourceProcessor->expects($this->once())->method('isLinked')->will($this->returnValue(false));
        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->equalTo(IoEvents::SOURCE_PROCESS))
        ;

        $this->listener->onFlush($this->getOnFlushEventArgs(new SourceMock(12345)));
        $this->listener->postFlush(new PostFlushEventArgs($this->entityManager));
    }

    /**
     * @param array $changeset
     * @param array $expectedResult
     *
     * @dataProvider changesetProvider
     */
    public function testFilterChangeset(array $changeset, array $expectedResult)
    {
        $changeset = $this->listener->filterChangeset($changeset);

        $this->assertEquals($expectedResult, $changeset);
    }

    public function changesetProvider()
    {
        $changeset1 = [
            'fieldA' => [
                [
                    'fieldB' => [
                        'fieldC' => (double) 2,
                    ],
                    'fieldD' => (int) 2,
                ],
                [
                    'fieldB' => [
                        'fieldC' => (int) 2.45,
                    ],
                    'fieldD' => (double) 2,
                ],
            ],
        ];

        $changeset2 = [
            'fieldA' => [
                [
                    'fieldB' => '2.4',
                    'fieldD' => (float) 2.0,
                ],
                [
                    'fieldB' => (float) 2.4,
                    'fieldD' => (int) 2,
                ],
            ],
        ];

        $changeset3 = [
            'fieldA' => [
                [
                    'fieldB' => '2.5',
                    'fieldD' => (float) 2.0,
                ],
                [
                    'fieldB' => (float) 2.3,
                    'fieldD' => (int) 2,
                ],
            ],
        ];

        $changeset4 = [
            'fieldA' => [
                [
                    'fieldB' => [2],
                ],
                [
                    'fieldB' => (float) 2.3,
                ],
            ],
        ];

        return [
            [$changeset1, []],
            [$changeset2, []],
            [$changeset3, [
                'fieldA' => ['fieldB' => '2.5'],
            ]],
            [$changeset4, [
                'fieldA' => ['fieldB' => [2]],
            ]],
        ];
    }
}

class SourceModificationListenerMock extends SourceModificationListener
{
    public function visibleIsSourceModified(SourceMock $source, UnitOfWork $uow)
    {
        return $this->isSourceModified($source, $uow);
    }
}
