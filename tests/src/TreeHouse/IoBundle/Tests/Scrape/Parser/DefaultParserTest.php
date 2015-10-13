<?php

namespace TreeHouse\IoBundle\Tests\Scrape\Parser;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use TreeHouse\Feeder\Event\FailedItemModificationEvent;
use TreeHouse\Feeder\Exception\FilterException;
use TreeHouse\Feeder\Exception\ModificationException;
use TreeHouse\Feeder\Exception\ValidationException;
use TreeHouse\Feeder\FeedEvents;
use TreeHouse\Feeder\Modifier\Item\Filter\CallbackFilter;
use TreeHouse\Feeder\Modifier\Item\ModifierInterface;
use TreeHouse\Feeder\Modifier\Item\Transformer\CallbackTransformer;
use TreeHouse\Feeder\Modifier\Item\Validator\CallbackValidator;
use TreeHouse\IoBundle\Entity\Scraper;
use TreeHouse\IoBundle\Scrape\Parser\DefaultParser;
use TreeHouse\IoBundle\Scrape\ScrapedItemBag;

class DefaultParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ModifierInterface
     */
    protected $modifier;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->modifier = new CallbackTransformer(function () {});
    }

    public function testConstructor()
    {
        $parser = new DefaultParser();

        $this->assertInstanceOf(DefaultParser::class, $parser);
        $this->assertInstanceOf(EventDispatcherInterface::class, $parser->getEventDispatcher());
    }

    public function testAddModifier()
    {
        $parser = new DefaultParser();
        $parser->addModifier($this->modifier);
        $parser->addModifier($this->modifier);

        $this->assertEquals([0 => $this->modifier, 1 => $this->modifier], $parser->getModifiers());
    }

    public function testAddModifierAt()
    {
        $parser = new DefaultParser();
        $parser->addModifier($this->modifier, 2);

        $this->assertEquals([2 => $this->modifier], $parser->getModifiers());
    }

    public function testAddModifierSort()
    {
        $parser = new DefaultParser();
        $parser->addModifier($this->modifier, 2);
        $parser->addModifier($this->modifier, 1);

        $this->assertEquals([1 => $this->modifier, 2 => $this->modifier], $parser->getModifiers());
    }

    public function testHasModifierAt()
    {
        $parser = new DefaultParser();

        $this->assertFalse($parser->hasModifierAt(1));

        $parser->addModifier($this->modifier, 1);

        $this->assertTrue($parser->hasModifierAt(1));
    }

    public function testRemoveModifier()
    {
        $parser = new DefaultParser();
        $parser->addModifier($this->modifier);

        $this->assertCount(1, $parser->getModifiers());

        $parser->removeModifier($this->modifier);

        $this->assertEmpty($parser->getModifiers());
    }

    public function testRemoveModifierAt()
    {
        $parser = new DefaultParser();
        $parser->addModifier($this->modifier, 1);
        $parser->addModifier($this->modifier, 2);
        $parser->addModifier($this->modifier, 3);

        $this->assertTrue($parser->hasModifierAt(1));
        $this->assertTrue($parser->hasModifierAt(2));
        $this->assertTrue($parser->hasModifierAt(3));

        $parser->removeModifierAt(2);

        $this->assertTrue($parser->hasModifierAt(1));
        $this->assertFalse($parser->hasModifierAt(2));
        $this->assertTrue($parser->hasModifierAt(3));
    }

    public function testParse()
    {
        $modifier = new CallbackTransformer(function (ParameterBag $item) {
            $item->set('foo', 'bar');
        });

        $item = $this->createItem();

        $parser = new DefaultParser();
        $parser->addModifier($modifier);
        $parser->parse($item);

        $this->assertEquals('bar', $item->get('foo'));
    }

    /**
     * @expectedException \TreeHouse\Feeder\Exception\FilterException
     */
    public function testParseFilterException()
    {
        $modifier = new CallbackFilter(function (ParameterBag $item) {
            throw new FilterException();
        });

        $item = $this->createItem();

        $parser = new DefaultParser();
        $parser->addModifier($modifier);
        $parser->parse($item);
    }

    /**
     * @expectedException \TreeHouse\Feeder\Exception\ValidationException
     */
    public function testParseValidationException()
    {
        $modifier = new CallbackValidator(function (ParameterBag $item) {
            throw new ValidationException();
        });

        $item = $this->createItem();

        $parser = new DefaultParser();
        $parser->addModifier($modifier);
        $parser->parse($item);
    }

    /**
     * @expectedException \TreeHouse\Feeder\Exception\ModificationException
     */
    public function testParseModificationExceptionWithoutContinue()
    {
        $modifier = new CallbackTransformer(function (ParameterBag $item) {
            throw new ModificationException();
        });

        $item = $this->createItem();

        $parser = new DefaultParser();
        $parser->addModifier($modifier, 1, false);
        $parser->parse($item);
    }

    public function testParseModificationExceptionWithContinue()
    {
        $modifier = new CallbackTransformer(function (ParameterBag $item) {
            throw new ModificationException();
        });

        $item = $this->createItem();

        $parser = new DefaultParser();
        $parser->addModifier($modifier, 1, true);
        $parser->parse($item);

        $this->assertInstanceOf(ScrapedItemBag::class, $item);
    }

    public function testParseModificationExceptionWithEvent()
    {
        $modifier = new CallbackTransformer(function (ParameterBag $item) {
            throw new ModificationException();
        });

        $item = $this->createItem();

        $parser = new DefaultParser();
        $parser->addModifier($modifier, 1, false);
        $parser->getEventDispatcher()->addListener(
            FeedEvents::ITEM_MODIFICATION_FAILED,
            function (FailedItemModificationEvent $event) {
                $event->setContinue(true);
            }
        );
        $parser->parse($item);

        $this->assertInstanceOf(ScrapedItemBag::class, $item);
    }

    /**
     * @return ScrapedItemBag
     */
    protected function createItem()
    {
        $scraper = new Scraper();
        $url = 'http://example.org';
        $html = '<html><body>Test</body></html>';

        $item = new ScrapedItemBag($scraper, $url, $html);

        return $item;
    }
}
