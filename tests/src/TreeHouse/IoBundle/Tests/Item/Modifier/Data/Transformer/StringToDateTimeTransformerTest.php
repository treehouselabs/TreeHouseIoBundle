<?php

namespace TreeHouse\IoBundle\Tests\Item\Modifier\Data\Transformer;

use TreeHouse\Feeder\Exception\TransformationFailedException;
use TreeHouse\IoBundle\Item\Modifier\Data\Transformer\StringToDateTimeTransformer;

class StringToDateTimeTransformerTest extends \PHPUnit_Framework_TestCase
{
    /** @var StringToDateTimeTransformer */
    private $transformer;

    public function setUp()
    {
        $this->transformer = new StringToDateTimeTransformer();
    }

    /**
     * @dataProvider getDates
     */
    public function testDates(string $date, string $expectedDate)
    {
        $transformed = $this->transformer->transform($date);

        static::assertSame(
            $expectedDate,
            $transformed->format('Y-m-d')
        );
    }

    public function getDates()
    {
        yield ['2012-10-12', '2012-10-12'];
        yield ['2012/10/12','2012-10-12'];
        yield ['2012-10', '2012-10-01'];
        yield ['Sat, 21 Sep 2013 09:56:14 +0200', '2013-09-21'];
    }

    public function testTransformReturnsValueWhenGivenDateTime()
    {
        $now = new \DateTime();
        $transformed = $this->transformer->transform($now);

        static::assertSame($transformed, $now);
    }

    public function testNullableDates()
    {
        static::assertNull(
            $this->transformer->transform(null)
        );

        static::assertNull(
            $this->transformer->transform('')
        );

        static::assertNull(
            $this->transformer->transform('0')
        );
    }

    /**
     * @dataProvider getInvalidDates
     */
    public function testInvalidDates($date)
    {
        $this->setExpectedException(TransformationFailedException::class);

        $this->transformer->transform($date);
    }

    public function getInvalidDates()
    {
        yield 'not a date' => ['not a date'];
        yield 'invalid format' => ['-1-11-30'];
        yield 'too old' => ['1800-12-23'];
        yield 'empty' => ['0000-00-00'];
    }
}
