<?php

namespace TreeHouse\IoBundle\Tests\Import\Modifier\Item\Filter;

use TreeHouse\IoBundle\Import\Feed\FeedItemBag;
use TreeHouse\IoBundle\Item\Modifier\Item\Filter\ModifiedItemFilter;
use TreeHouse\IoBundle\Model\SourceInterface;
use TreeHouse\IoBundle\Source\Manager\ImportSourceManager;
use TreeHouse\IoBundle\Tests\Mock\FeedMock;
use TreeHouse\IoBundle\Tests\Mock\SourceMock;

class ModifiedItemFilterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ImportSourceManager
     */
    protected $sourceManager;

    /**
     * @var SourceInterface
     */
    protected $source;

    protected function setUp()
    {
        $this->source = new SourceMock(123);

        $this->sourceManager = $this
            ->getMockBuilder(ImportSourceManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['findSource'])
            ->getMock()
        ;

        $this->sourceManager
            ->expects($this->any())
            ->method('findSource')
            ->will($this->returnValue($this->source))
        ;
    }

    /**
     * @dataProvider      getUnmodifiedItems
     * @expectedException \TreeHouse\Feeder\Exception\FilterException
     */
    public function testUnmodifiedItems(\DateTime $sourceDate, \DateTime $itemDate = null)
    {
        $this->source->setDatetimeModified($sourceDate);
        $item = new FeedItemBag(new FeedMock(1234), '123abc');
        $item->setDatetimeModified($itemDate);

        $filter = new ModifiedItemFilter($this->sourceManager);
        $filter->filter($item);
    }

    public static function getUnmodifiedItems()
    {
        return [
            [new \DateTime('2013-11-18'), new \DateTime('2013-11-17')],
        ];
    }

    /**
     * @dataProvider getModifiedItems
     */
    public function testModifiedItems(\DateTime $sourceDate, \DateTime $itemDate = null)
    {
        $this->source->setDatetimeModified($sourceDate);
        $item = new FeedItemBag(new FeedMock(1234), '123abc');
        $item->setDatetimeModified($itemDate);

        $filter = new ModifiedItemFilter($this->sourceManager);
        $filter->filter($item);
    }

    public static function getModifiedItems()
    {
        return [
            [new \DateTime('2013-11-18'), new \DateTime('2013-11-20')],
            [new \DateTime('2013-11-18'), null],
        ];
    }
}
