<?php

namespace TreeHouse\IoBundle\Tests\Scrape\Parser;

use TreeHouse\Feeder\Modifier\Data\Transformer\TransformerInterface;
use TreeHouse\Feeder\Modifier\Item\Filter\FilterInterface;
use TreeHouse\Feeder\Modifier\Item\ModifierInterface;
use TreeHouse\Feeder\Modifier\Item\Transformer\DataTransformer;
use TreeHouse\Feeder\Modifier\Item\Validator\ValidatorInterface;
use TreeHouse\IoBundle\Scrape\Parser\ParserBuilder;
use TreeHouse\IoBundle\Scrape\Parser\ParserInterface;
use TreeHouse\IoBundle\Scrape\Parser\Type\ParserTypeInterface;

class ParserBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ParserBuilder
     */
    protected $builder;

    protected function setUp()
    {
        $this->builder = new ParserBuilder();
    }

    public function testConstructor()
    {
        $this->assertInstanceOf(ParserBuilder::class, $this->builder);
    }

    public function testBuildCallsBuildOnType()
    {
        $type = $this->getParserTypeMock();
        $type->expects($this->once())->method('build');

        $this->builder->build($type, []);
    }

    public function testBuildReturnsParser()
    {
        $type   = $this->getParserTypeMock();
        $parser = $this->builder->build($type, []);

        $this->assertInstanceOf(ParserInterface::class, $parser);
    }

    public function testAddModifier()
    {
        $this->builder->addModifier($this->getModifierMock());
        $modifiers = $this->builder->getModifiers();
        $this->assertEquals(0, key($modifiers), '->addModifier() adds a modifier at position 0');

        $this->builder->addModifier($this->getModifierMock());
        $modifiers = $this->builder->getModifiers();
        end($modifiers);
        $this->assertEquals(1, key($modifiers), '->addModifier() auto-increments position');
    }

    /**
     * @expectedException        \InvalidArgumentException
     * @expectedExceptionMessage There already is a modifier at position
     */
    public function testAddModifierDuplicatePosition()
    {
        $this->builder->addModifier($this->getModifierMock(), 1);
        $this->builder->addModifier($this->getModifierMock(), 1);
    }

    public function testAddModifierContinueValue()
    {
        $this->builder->addModifier($this->getModifierMock());
        list(, $continue) = $this->builder->getModifiers()[0];
        $this->assertEquals(true, $continue, '->addModifier() defaults "continue" option to true');

        $this->builder->addModifier($this->getFilterMock());
        list(, $continue) = $this->builder->getModifiers()[0];
        $this->assertEquals(true, $continue, '->addModifier() sets "continue" option to false for filters');

        $this->builder->addModifier($this->getValidatorMock());
        list(, $continue) = $this->builder->getModifiers()[0];
        $this->assertEquals(true, $continue, '->addModifier() sets "continue" option to false for validators');
    }

    public function testAddDataTransformer()
    {
        $this->builder->addTransformer($this->getTransformerMock(), 'foo');
        list($transformer) = $this->builder->getModifiers()[0];

        $this->assertInstanceOf(
            DataTransformer::class,
            $transformer,
            '->addTransformer() wraps a Transformer in a DataTransformer '
        );
    }

    public function testRemoveModifier()
    {
        $modifierA = $this->getModifierMock();
        $modifierB = $this->getFilterMock();
        $modifierC = $this->getValidatorMock();

        $this->builder->addModifier($modifierA);
        $this->builder->addModifier($modifierB);
        $this->builder->addModifier($modifierC);

        $this->builder->removeModifier($modifierB);

        $this->assertSame(2, sizeof($this->builder->getModifiers()));
        $this->assertNotContains(
            $modifierB,
            array_map(function (array $mod) {
                list($modifier,) = $mod;

                return $modifier;
            }, $this->builder->getModifiers())
        );
    }

    public function testRemoveModifierAt()
    {
        $modifierA = $this->getModifierMock();
        $modifierB = $this->getFilterMock();
        $modifierC = $this->getValidatorMock();

        $this->builder->addModifier($modifierA, 10);
        $this->builder->addModifier($modifierB, 20);
        $this->builder->addModifier($modifierC, 30);

        $this->builder->removeModifierAt(20);

        $this->assertSame(2, sizeof($this->builder->getModifiers()));
        $this->assertNotContains(
            $modifierB,
            array_map(function (array $mod) {
                list($modifier,) = $mod;

                return $modifier;
            }, $this->builder->getModifiers())
        );
    }

    /**
     * @expectedException        \OutOfBoundsException
     * @expectedExceptionMessage There is no modifier at position
     */
    public function testRemoveModifierUnknownPosition()
    {
        $this->builder->addModifier($this->getModifierMock(), 10);
        $this->builder->addModifier($this->getModifierMock(), 20);

        $this->builder->removeModifierAt(15);
    }

    public function testModifierAddedToParser()
    {
        $type = $this->getParserTypeMock();

        $modifier = $this->getModifierMock();
        $this->builder->addModifier($modifier);

        $parser = $this->builder->build($type, []);

        $this->assertSame(1, sizeof($parser->getModifiers()));
        $this->assertContains(
            $modifier,
            $parser->getModifiers()
        );
    }

    /**
     * @return ParserTypeInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getParserTypeMock()
    {
        $mock = $this
            ->getMockBuilder(ParserTypeInterface::class)
            ->getMockForAbstractClass()
        ;

//        $mock
//            ->expects($this->any())
//            ->method('getItemName')
//            ->will($this->returnValue('node'))
//        ;

        return $mock;
    }

    /**
     * @return ModifierInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getModifierMock()
    {
        return $this->getMockForAbstractClass(ModifierInterface::class);
    }

    /**
     * @return FilterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getFilterMock()
    {
        return $this->getMockForAbstractClass(FilterInterface::class);
    }

    /**
     * @return ValidatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getValidatorMock()
    {
        return $this->getMockForAbstractClass(ValidatorInterface::class);
    }

    /**
     * @return TransformerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getTransformerMock()
    {
        return $this->getMockForAbstractClass(TransformerInterface::class);
    }
}
