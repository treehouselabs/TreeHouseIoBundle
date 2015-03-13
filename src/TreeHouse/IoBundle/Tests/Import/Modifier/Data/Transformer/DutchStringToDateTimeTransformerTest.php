<?php

namespace TreeHouse\IoBundle\Tests\Import\Modifier\Data\Transformer;

use TreeHouse\IoBundle\Item\Modifier\Data\Transformer\DutchStringToDateTimeTransformer;

class DutchStringToDateTimeTransformerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DutchStringToDateTimeTransformer
     */
    protected $transformer;

    /**
     * @var \DateTimeZone
     */
    protected $timezone;

    public function setUp()
    {
        $this->timezone    = $this->getDefaultTimeZone();
        $this->transformer = new DutchStringToDateTimeTransformer([], $this->timezone);
    }

    /**
     * @dataProvider getDates
     */
    public function testDates($date, \DateTime $expectedDate)
    {
        $transformed = $this->transformer->transform($date);

        $hour = date('H');
        $expectedDate->setTime($hour, 0);
        $transformed->setTime($hour, 0);

        $this->assertSame(
            $expectedDate->format('r'),
            $transformed->format('r'),
            sprintf(
                'Expecting date "%s" to be transformed to "%s", got "%s" instead',
                $date,
                $expectedDate->format('r'),
                $transformed->format('r')
            )
        );
    }

    public function getDates()
    {
        return [
            ['direkt',          $this->getDateTime('now')],
            ['direct',          $this->getDateTime('now')],
            ['per direkt',      $this->getDateTime('now')],
            ['per direct',      $this->getDateTime('now')],
            ['gelijk',          $this->getDateTime('now')],
            ['heden',           $this->getDateTime('now')],
            ['sinds 2 mnd',     $this->getDateTime('-2 months')],
            ['2 mnd',           $this->getDateTime('-2 months')],
            ['sinds 1 maand',   $this->getDateTime('-1 month')],
            ['1 maand',         $this->getDateTime('-1 month')],
            ['sinds 2 maanden', $this->getDateTime('-2 months')],
            ['2 maanden',       $this->getDateTime('-2 months')],
            ['eind juni',       $this->getDateTime(date('Y').'-06-25')],
            ['begin mei',       $this->getDateTime(date('Y').'-05-05')],
            ['oktober 2012',    $this->getDateTime('2012-10-01')],
            ['oktober \'12',    $this->getDateTime('2012-10-01')],
            ['12 oktober 2012', $this->getDateTime('2012-10-12')],
            ['12 oktober \'12', $this->getDateTime('2012-10-12')],
            ['1mei 2012',       $this->getDateTime('2012-05-01')],
            ['12-10-2012',      $this->getDateTime('2012-10-12')],
            ['2012-10-26',      $this->getDateTime('2012-10-26')],
            ['2012-10',         $this->getDateTime('2012-10-01')],
            ['01-10-\'12',      $this->getDateTime('2012-10-01')],
            ['10 april',        $this->getDateTime(date('Y').'-04-10')],
            ['april 10',        $this->getDateTime(date('Y').'-04-10')],
            ['april',           $this->getDateTime(date('Y').'-04-01')],
            ['2012',            $this->getDateTime('2012-01-01')],
            ['Sat, 21 Sep 2013 09:56:14 +0200', $this->getDateTime('2013-09-21', new \DateTimeZone('Europe/Amsterdam'))],
            ['1-4-2012',        $this->getDateTime('2012-04-01')],
            ['01/01/2013',      $this->getDateTime('2013-01-01')],
            ['31/12/2013',      $this->getDateTime('2013-12-31')],
            ['23-01-14',        $this->getDateTime('2014-01-23')],
        ];
    }

    public function testTransformReturnsValueWhenGivenDateTime()
    {
        $now         = new \DateTime();
        $transformed = $this->transformer->transform($now);

        $this->assertSame($transformed, $now);
    }

    /**
     * @dataProvider getNullableDates
     */
    public function testNullableDates($date)
    {
        $transformed = $this->transformer->transform($date);

        $this->assertNull($transformed);
    }

    public function getNullableDates()
    {
        return [
            ['00-00-0000'],
            ['0000-00-00'],
            ['0001-00-00'],
            ['2134-00-00'],
            ['2012-12-00'],
            ['2013-13-25'],
            ['2013-02-30'],
            ['2012-00-10'],
            ['10/16/2013'],
            [''],
            [null],
        ];
    }

    /**
     * @dataProvider getInvalidDates
     * @expectedException \TreeHouse\Feeder\Exception\ModificationException
     */
    public function testInvalidDates($date)
    {
        $this->transformer->transform($date);
    }

    public function getInvalidDates()
    {
        return [
            ['-1-11-30'],
            ['notk'],
        ];
    }

    /**
     * @param  string        $date
     * @param  \DateTimeZone $timezone
     * @return \DateTime
     */
    protected function getDateTime($date, \DateTimeZone $timezone = null)
    {
        $timezone = $timezone ?: $this->getDefaultTimeZone();

        return new \DateTime($date, $timezone);
    }

    /**
     * @return \DateTimeZone
     */
    protected function getDefaultTimeZone()
    {
        return new \DateTimeZone('UTC');
    }
}
