<?php

namespace TreeHouse\IoBundle\Tests\Item\Modifier\Data\Transformer;

use TreeHouse\Feeder\Modifier\Data\Transformer\TransformerInterface;
use TreeHouse\IoBundle\Item\Modifier\Data\Transformer\LocalizedStringToNumberTransformer;

class LocalizedStringToNumberTransformerTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $transformer = new LocalizedStringToNumberTransformer();

        $this->assertInstanceOf(TransformerInterface::class, $transformer);
    }

    /**
     * @dataProvider stringNumberProvider
     */
    public function testTransform($str, $number)
    {
        $transformer = new LocalizedStringToNumberTransformer('nl_NL');

        $this->assertSame((double) $number, $transformer->transform($str));
    }

    public function stringNumberProvider()
    {
        return [
            ['2 apples', 2],
            ['there are 5 apples', 5],
            ['volume: 123m3', 123],
        ];
    }
}
