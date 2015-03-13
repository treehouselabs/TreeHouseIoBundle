<?php

namespace TreeHouse\IoBundle\Tests\Import\FeedType;

use TreeHouse\IoBundle\Import\Feed\Type\DefaultFeedType;

/**
 * @group functional
 */
class DefaultFieldMappingTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param array $mapping
     * @param array $entity
     * @param array $extra
     * @param array $unmapped
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|DefaultFeedType
     */
    protected function getFeedTypeMock(array $mapping = null, array $entity = null, array $extra = null, array $unmapped = null)
    {
        $mock = $this
            ->getMockBuilder(DefaultFeedType::class)
            ->disableOriginalConstructor()
            ->setMethods(['getMapping', 'getEntityFields', 'getExtraMappedFields', 'getUnmappedFields'])
            ->getMockForAbstractClass()
        ;

        if (null !== $mapping) {
            $mock
                ->expects($this->once())
                ->method('getMapping')
                ->will($this->returnValue($mapping))
            ;
        }

        if (null !== $entity) {
            $mock
                ->expects($this->once())
                ->method('getEntityFields')
                ->will($this->returnValue($entity))
            ;
        }

        if (null !== $extra) {
            $mock
                ->expects($this->once())
                ->method('getExtraMappedFields')
                ->will($this->returnValue($extra))
            ;
        }

        if (null !== $unmapped) {
            $mock
                ->expects($this->once())
                ->method('getUnmappedFields')
                ->will($this->returnValue($unmapped))
            ;
        }

        return $mock;
    }

    public function testMappedFields()
    {
        $mock = $this->getFeedTypeMock(['foo' => 'bar'], [], ['foobar'], ['baz']);
        $fields = $this->invokeMethod($mock, 'getMappedFields');

        $this->assertInternalType('array', $fields, 'Expecting an array of fields');
        $this->assertNotEmpty($fields, 'Expecting a non-empty array of fields');
        $this->assertNotContains('id', $fields, 'The identifier should not exist in mapped fields');
        $this->assertContains('bar', $fields, 'Fields specified in getMapping() should exist in mapped fields');
        $this->assertContains('foobar', $fields, 'Extra mapping should be added');
        $this->assertNotContains('baz', $fields, 'Unmapped fields should be removed');
    }

    /**
     * Call protected/private method of a class.
     *
     * @param object &$object    Instantiated object that we will run method on.
     * @param string $methodName Method name to call
     * @param array  $parameters Array of parameters to pass into method.
     *
     * @return mixed Method return.
     */
    protected function invokeMethod(&$object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}
