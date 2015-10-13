<?php

namespace TreeHouse\IoBundle\Tests\Item\Modifier\Data\Transformer;

use TreeHouse\IoBundle\Item\Modifier\Data\Transformer\MultiLineToSingleLineTransformer;

class MultiLineToSingleLineTransformerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider transformProvider
     */
    public function testTransform($value, $expected)
    {
        $transformer = new MultiLineToSingleLineTransformer();

        $this->assertEquals($expected, $transformer->transform($value));
    }

    public function transformProvider()
    {
        return [
            ["Line1\nLine2", 'Line1 Line2'],
            ["Line1\n\nLine2", 'Line1 Line2'],
            ["Line1\n \nLine2\n", 'Line1 Line2'],
        ];
    }
}
