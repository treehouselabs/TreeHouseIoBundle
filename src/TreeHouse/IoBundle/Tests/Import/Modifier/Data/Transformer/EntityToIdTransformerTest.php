<?php

namespace TreeHouse\IoBundle\Tests\Import\Modifier\Data\Transformer;

use Symfony\Component\HttpFoundation\ParameterBag;
use TreeHouse\IoBundle\Item\Modifier\Data\Transformer\EntityToIdTransformer;

class EntityToIdTransformerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EntityToIdTransformer
     */
    protected $transformer;

    /**
     * @var EntityMock
     */
    protected $entity;

    public function setUp()
    {
        $this->entity = new EntityMock();

        $meta = $this
            ->getMockBuilder('Doctrine\Common\Persistence\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->setMethods(['getClassMetadata'])
            ->getMockForAbstractClass();

        $meta->expects($this->any())
            ->method('getIdentifierValues')
            ->will($this->returnValue(['id' => $this->entity->getId()]));

        $em = $this
            ->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['getClassMetadata'])
            ->getMock();

        $em->expects($this->any())
            ->method('getClassMetadata')
            ->will($this->returnValue($meta));

        $this->transformer = new EntityToIdTransformer($em);
    }

    public function testTransform()
    {
        $this->assertSame(
            ['id' => $this->entity->getId()],
            $this->transformer->transform($this->entity, 'realtor', new ParameterBag())
        );
    }
}

class EntityMock
{
    public function getId()
    {
        return 1234;
    }
}
