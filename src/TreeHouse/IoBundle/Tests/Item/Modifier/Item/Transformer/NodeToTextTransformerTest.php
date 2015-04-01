<?php

namespace TreeHouse\IoBundle\Tests\Item\Modifier\Item\Transformer;

use Symfony\Component\HttpFoundation\ParameterBag;
use TreeHouse\IoBundle\Item\Modifier\Item\Transformer\NodeToTextTransformer;

class NodeTextTransformerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ParameterBag
     */
    protected $item;

    protected function setUp()
    {
        $this->item = new ParameterBag([
            'id'   => 1234,
            'img'  => [
                '#'    => 'Fill Murray',
                '@src' => 'http://www.fillmurray.com/g/200/300',
            ],
            'link' => [
                '#'    => 'http://www.fillmurray.com',
                '@rel' => 'external',
            ],
        ]);
    }

    /**
     * @expectedException \TreeHouse\Feeder\Exception\UnexpectedTypeException
     */
    public function testInvalidConstructor()
    {
        new NodeToTextTransformer(1234);
    }

    public function testReplaceField()
    {
        $transformer = new NodeToTextTransformer('img');
        $transformer->transform($this->item);

        $this->assertEquals(
            [
                'id'   => 1234,
                'img'  => 'Fill Murray',
                'link' => [
                    '#'    => 'http://www.fillmurray.com',
                    '@rel' => 'external',
                ],
            ],
            $this->item->all()
        );
    }

    public function testReplaceAll()
    {
        $transformer = new NodeToTextTransformer();
        $transformer->transform($this->item);

        $this->assertEquals(
            [
                'id'   => 1234,
                'img'  => 'Fill Murray',
                'link' => 'http://www.fillmurray.com',
            ],
            $this->item->all()
        );
    }

    public function testReplaceFieldNotFound()
    {
        $transformer = new NodeToTextTransformer('object');
        $transformer->transform($this->item);

        $this->assertEquals(
            [
                'id'   => 1234,
                'img'  => [
                    '#'    => 'Fill Murray',
                    '@src' => 'http://www.fillmurray.com/g/200/300',
                ],
                'link' => [
                    '#'    => 'http://www.fillmurray.com',
                    '@rel' => 'external',
                ],
            ],
            $this->item->all()
        );
    }
}
