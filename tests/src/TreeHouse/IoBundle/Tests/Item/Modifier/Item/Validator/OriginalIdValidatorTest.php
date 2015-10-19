<?php

namespace TreeHouse\IoBundle\Tests\Item\Modifier\Item\Validator;

use TreeHouse\IoBundle\Import\Feed\FeedItemBag;
use TreeHouse\IoBundle\Item\Modifier\Item\Validator\OriginIdValidator;
use TreeHouse\IoBundle\Test\Mock\FeedMock;

class OriginalIdValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OriginIdValidator
     */
    protected $validator;

    protected function setUp()
    {
        $this->validator = new OriginIdValidator();
    }

    /**
     * @dataProvider getInvalidOrigins
     * @expectedException \TreeHouse\Feeder\Exception\ValidationException
     */
    public function testInvalidOrigin($originalId)
    {
        $item = new FeedItemBag(new FeedMock(123), $originalId);
        $this->validator->validate($item);
    }

    public static function getInvalidOrigins()
    {
        return [
            ['0'],
            [0],
            [null],
            [''],
            [' '],
            [[]],
        ];
    }

    /**
     * @dataProvider getValidOrigins
     */
    public function testValidOrigin($originalId)
    {
        $item = new FeedItemBag(new FeedMock(123), $originalId);
        $this->validator->validate($item);
    }

    public static function getValidOrigins()
    {
        return [
            [1],
            ['1234'],
            ['123foo'],
            ['foo-1234-bar'],
            ['foo_1234/bar_'],
            ['1234 foobar'],
        ];
    }
}
