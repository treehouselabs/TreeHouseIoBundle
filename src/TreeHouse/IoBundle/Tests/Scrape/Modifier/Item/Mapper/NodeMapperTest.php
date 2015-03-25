<?php

namespace TreeHouse\IoBundle\Tests\Scrape\Modifier\Item\Mapper;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\ParameterBag;
use TreeHouse\IoBundle\Scrape\Modifier\Item\Mapper\NodeMapper;

class NodeMapperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getConstructorMappingData
     */
    public function testConstructor($mapping)
    {
        $mapper = new NodeMapper($mapping);

        $this->assertInstanceOf(NodeMapper::class, $mapper);
    }

    public function getConstructorMappingData()
    {
        return [
            [
                ['foo' => 'bar'],
            ],
            [
                [
                    'foo' => 'bar',
                    'bar' => ['baz', 'extractText'],
                ]
            ],
            [
                [
                    'foo' => 'bar',
                    'bar' => ['baz', function () { }],
                ]
            ]
        ];
    }

    /**
     * @dataProvider getInvalidConstructorMappingData
     * @expectedException \InvalidArgumentException
     */
    public function testConstructorWithInvalidMapping($mapping)
    {
        new NodeMapper($mapping);
    }

    public function getInvalidConstructorMappingData()
    {
        return [
            [
                ['foo' => []],
            ],
            [
                [
                    'bar' => ['baz', []],
                ]
            ],
        ];
    }

    public function testAddMapping()
    {
        $mapper = new NodeMapper([]);
        $mapper->addMapping('foo', 'bar');

        $this->assertEquals(['foo' => 'bar'], $mapper->getMapping());
    }

    public function testAddMappingWithExtractor()
    {
        $mapper = new NodeMapper([]);
        $mapper->addMapping('foo', 'bar', 'extractText');

        $this->assertEquals(['foo' => 'bar'], $mapper->getMapping());
    }

    public function testAddMappingWithExtractorClosure()
    {
        $mapper = new NodeMapper([]);
        $mapper->addMapping('foo', 'bar', function () {});

        $this->assertEquals(['foo' => 'bar'], $mapper->getMapping());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testAddMappingWithInvalidExtractor()
    {
        $mapper = new NodeMapper([]);
        $mapper->addMapping('foo', 'bar', []);
    }

    /**
     * @expectedException \LogicException
     */
    public function testMapWithoutCrawler()
    {
        $mapper = new NodeMapper([]);
        $mapper->map(new ParameterBag());
    }

    public function testMap()
    {
        $crawler = new Crawler('<html><body><h1 class="test">Test</h1></body></html>');

        $mapper = new NodeMapper([
            'foo' => 'h1.test',
        ]);
        $mapper->setCrawler($crawler);
        $item = $mapper->map(new ParameterBag());

        $this->assertEquals('Test', $item->get('foo'));
    }

    public function testMapWithExtractingText()
    {
        $crawler = new Crawler('<html><body><h1 class="test">Captain <small>subtext</small></small></h1></body></html>');

        $mapper = new NodeMapper([
            'foo' => ['h1.test', 'extractText'],
        ]);
        $mapper->setCrawler($crawler);
        $item = $mapper->map(new ParameterBag());

        $this->assertEquals('Captain subtext', $item->get('foo'));
    }
}
