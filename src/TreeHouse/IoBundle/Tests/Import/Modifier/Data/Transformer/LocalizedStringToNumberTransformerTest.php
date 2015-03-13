<?php

namespace TreeHouse\IoBundle\Tests\Import\Modifier\Data\Transformer;

use TreeHouse\IoBundle\Item\Modifier\Data\Transformer\LocalizedStringToNumberTransformer;

/**
 * Test the LocalizedStringToNumberTransformer
 */
class LocalizedStringToNumberTransformerTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $transformer = new LocalizedStringToNumberTransformer(\NumberFormatter::TYPE_INT32);

        $this->assertInstanceOf(LocalizedStringToNumberTransformer::class, $transformer);
    }

    /**
     * @dataProvider stringNumberProvider
     */
    public function testTransform($givenStringNumber, $givenType, $givenPrecision, $expectedType, $expectedValue)
    {
        $transformer = new LocalizedStringToNumberTransformer($givenType, $givenPrecision);

        $result = $transformer->transform($givenStringNumber);

        $this->assertEquals($expectedType, gettype($result));

        $this->assertEquals($expectedValue, $result);
    }

    public function stringNumberProvider()
    {
        return [
            ['1.23', \NumberFormatter::TYPE_DOUBLE, 2, 'double', 1.23],
            ['1.2', \NumberFormatter::TYPE_INT32, 2, 'integer', 1],
            ['2', \NumberFormatter::TYPE_INT64, 0, 'integer', 2]
        ];
    }
}
