<?php

namespace TreeHouse\IoBundle\Tests\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use TreeHouse\IoBundle\Entity\Import;
use TreeHouse\IoBundle\EventListener\ImportRemovalListener;
use TreeHouse\IoBundle\Import\ImportStorage;
use TreeHouse\IoBundle\Import\Log\ItemLoggerInterface;

class ImportRemovalListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param Import $entity
     *
     * @return LifecycleEventArgs
     */
    public function getLifecycleEventArgs($entity)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|EntityManagerInterface $manager */
        $manager = $this
            ->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        return new LifecycleEventArgs($entity, $manager);
    }

    public function testRemoveLogOnImportRemoval()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ImportStorage $storage */
        $storage = $this
            ->getMockBuilder(ImportStorage::class)
            ->setConstructorArgs([sys_get_temp_dir()])
            ->setMethods(['removeImport'])
            ->getMock()
        ;

        $logger = $this
            ->getMockBuilder(ItemLoggerInterface::class)
            ->setMethods(['removeLog'])
            ->getMockForAbstractClass()
        ;

        $logger->expects($this->once())->method('removeLog');
        $storage->expects($this->once())->method('removeImport');

        $listener = new ImportRemovalListener($storage, $logger);
        $args = $this->getLifecycleEventArgs(new Import());

        $listener->preRemove($args);
    }
}
