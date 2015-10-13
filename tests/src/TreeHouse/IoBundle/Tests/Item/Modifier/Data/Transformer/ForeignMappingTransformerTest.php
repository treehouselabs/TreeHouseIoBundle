<?php

namespace TreeHouse\IoBundle\Tests\Item\Modifier\Data\Transformer;

use TreeHouse\IoBundle\Item\Modifier\Data\Transformer\ForeignMappingTransformer;

class ForeignMappingTransformerTest extends \PHPUnit_Framework_TestCase
{
    public function testNull()
    {
        $transformer = new ForeignMappingTransformer('foo', [1 => 'bar']);

        $this->assertNull($transformer->transform(null));
    }

    public function testTransform()
    {
        $mapping = [
            0 => null,
            1 => 'once',
            2 => 'twice',
            3 => 'thrice',
        ];

        $transformer = new ForeignMappingTransformer('foo', $mapping);

        $this->assertSame(null, $transformer->transform(0), 'Value "0" should have mapped to NULL');
        $this->assertSame('twice', $transformer->transform('2'), 'Array keys can be strings or integers');
    }

    /**
     * @expectedException \TreeHouse\Feeder\Exception\TransformationFailedException
     */
    public function testMappingNotFound()
    {
        $mapping = [
            0 => null,
            1 => 'once',
            2 => 'twice',
            3 => 'thrice',
        ];

        $transformer = new ForeignMappingTransformer('foo', $mapping);
        $transformer->transform(4);
    }

    public function testTransformArray()
    {
        $mapping = [
            0 => null,
            1 => 'once',
            2 => 'twice',
            3 => 'thrice',
        ];

        $transformer = new ForeignMappingTransformer('foo', $mapping);

        $this->assertSame(['once', 'twice'], $transformer->transform([1, 2]), 'Transform multiple values');
        $this->assertSame(
            [
                'once',
                'twice',
                'twice',
                'thrice',
            ],
            $transformer->transform([
                0 => [1, 2],
                1 => [2, 3],
            ]),
            'Merge multidimensional arrays'
        );
    }
}
