<?php

namespace TreeHouse\IoBundle\Tests\Scrape\Parser;

use Symfony\Component\Finder\Finder;
use TreeHouse\IoBundle\Entity\Scraper;
use TreeHouse\IoBundle\Scrape\Parser\ParserBuilder;
use TreeHouse\IoBundle\Scrape\Parser\ParserInterface;
use TreeHouse\IoBundle\Scrape\ScrapedItemBag;
use TreeHouse\IoBundle\Tests\Item\ItemFixture;
use TreeHouse\IoBundle\Tests\TestCase;

abstract class AbstractParserTypeTest extends TestCase
{
    /**
     * @var string
     */
    protected static $url = 'http://example.org';

    /**
     * @return string
     */
    abstract protected function getParserType();

    /**
     * @param Scraper $scraper
     *
     * @return ParserInterface
     */
    protected function getParser(Scraper $scraper)
    {
        $parserType = $this->get('tree_house.io.scrape.scraper_factory')->getParserType($scraper->getParser());
        $options = array_merge(['scraper' => $scraper], $scraper->getParserOptions());

        return (new ParserBuilder())->build($parserType, $options);
    }

    /**
     * @param string $parser
     *
     * @return Scraper
     */
    protected function getScraperEntity($parser)
    {
        return $this
            ->getEntityManager()
            ->createQuery('SELECT s, o FROM TreeHouseIoBundle:Scraper s JOIN s.origin o WHERE s.parser = :parser')
            ->setParameter('parser', $parser)
            ->setMaxResults(1)
            ->getOneOrNullResult()
            ;
    }

    /**
     * @return string[]
     */
    public function getFixtureNames()
    {
        $fixtures = [];

        $refl = new \ReflectionClass(get_class($this));
        $dir  = sprintf('%s/fixtures/%s', dirname($refl->getFilename()), $this->getParserType());

        $files = Finder::create()->files()->name('*.html')->in($dir);

        /** @var \SplFileInfo $file */
        foreach ($files as $file) {
            $fixtures[] = [$file->getBasename('.html')];
        }

        if (empty($fixtures)) {
            $this->markTestSkipped(sprintf('No fixtures for %s created', $this->getParserType()));
        }

        return $fixtures;
    }

    /**
     * @dataProvider getFixtureNames
     *
     * @param string $fixtureName
     */
    public function testFixtures($fixtureName)
    {
        $this->assertFixture($this->getItemFixture($this->getParserType(), $fixtureName));
    }
    /**
     * @param ItemFixture $fixture
     */
    protected function assertOriginalId(ItemFixture $fixture)
    {
        $this->assertEquals(
            $fixture->getExpectedItem()->getOriginalId(),
            $fixture->getActualItem()->getOriginalId()
        );
    }
    /**
     * @param ItemFixture $fixture
     */
    protected function assertOriginalUrl(ItemFixture $fixture)
    {
        $this->assertEquals(
            $fixture->getExpectedItem()->getOriginalUrl(),
            $fixture->getActualItem()->getOriginalUrl()
        );
    }
    /**
     * @param ItemFixture $fixture
     */
    protected function assertFixture(ItemFixture $fixture)
    {
        $this->assertOriginalId($fixture);
        $this->assertOriginalUrl($fixture);
        $expected = $fixture->getExpectedItem()->all();
        $actual   = $fixture->getActualItem()->all();
        foreach ($expected as $key => $expectedValue) {
            $this->assertArrayHasKey(
                $key,
                $actual,
                sprintf('Key "%s" is not in item, when it should be', $key)
            );
            $actualValue = $actual[$key];
            $this->normalizeValues($key, $expectedValue, $actualValue);
            $this->assertValue($key, $expectedValue, $actualValue);
            unset($actual[$key]);
        }
        if (!empty($actual)) {
            $this->fail(
                sprintf('The following keys in the modified item are not tested: %s', json_encode(array_keys($actual)))
            );
        }
    }

    /**
     * Asserts a value
     *
     * @param $key
     * @param $expectedValue
     * @param $actualValue
     */
    protected function assertValue($key, $expectedValue, $actualValue)
    {
        // if either actual or expected is an object, use equality assertion (==),
        // otherwise use identity assertion (===)
        $isObject = false;
        foreach ([$expectedValue, $actualValue] as $test) {
            $isObject = is_object($test) || (is_array($test) && isset($test[0]) && is_object($test[0]));
            if ($isObject) {
                break;
            }
        }

        $assert = $isObject ? 'assertEquals' : 'assertSame';
        $this->$assert($expectedValue, $actualValue, sprintf('Key "%s" is not modified properly', $key));
    }

    /**
     * Normalizes values before asserting them
     *
     * @param string $key
     * @param mixed  $expectedValue
     * @param mixed  $actualValue
     */
    protected function normalizeValues($key, &$expectedValue, &$actualValue)
    {
        // some integers are modified to doubles, this is ok though
        if (is_integer($expectedValue) && is_double($actualValue)) {
            $expectedValue = (double) $expectedValue;
        }

        // the order of non-associative arrays does not matter
        if (is_array($expectedValue) && is_numeric(key($expectedValue)) && is_array($actualValue)) {
            sort($expectedValue);
            sort($actualValue);
        }

        // only test the day of dates
        foreach (['expectedValue', 'actualValue'] as $var) {
            if (is_string($$var) && preg_match('/^(\d{4}\-\d{2}\-\d{2})T[0-9\:\+]+$/', $$var, $matches)) {
                $$var = $matches[1];
            }
        }
    }

    /**
     * @param string $parserType
     * @param string $fixtureName
     *
     * @return ItemFixture
     */
    protected function getItemFixture($parserType, $fixtureName)
    {
        if (null === $scraper = $this->getScraperEntity($parserType)) {
            $this->markTestSkipped(sprintf('Add an origin with a %s scraper to the database first', $parserType));
        }

        $actual   = $this->getActualItemFixture($scraper, $parserType, $fixtureName);
        $expected = $this->getExpectedItemFixture($scraper, $parserType, $fixtureName);

        return new ItemFixture($actual, $expected);
    }

    /**
     * @param Scraper $scraper
     * @param string  $parserType
     * @param string  $fixtureName
     *
     * @return ScrapedItemBag
     */
    protected function getActualItemFixture(Scraper $scraper, $parserType, $fixtureName)
    {
        $refl = new \ReflectionClass(get_class($this));
        $html = file_get_contents(sprintf('%s/fixtures/%s/%s.html', dirname($refl->getFilename()), $parserType, $fixtureName));

        $item = new ScrapedItemBag($scraper, static::$url, $html);

        $parser = $this->getParser($scraper);
        $parser->parse($item);

        return $item;
    }

    /**
     * @param Scraper $scraper
     * @param string  $parserType
     * @param string  $fixtureName
     *
     * @return ScrapedItemBag
     */
    protected function getExpectedItemFixture(Scraper $scraper, $parserType, $fixtureName)
    {
        $refl = new \ReflectionClass(get_class($this));
        $phpFile = sprintf('%s/fixtures/%s/%s.php', dirname($refl->getFilename()), $parserType, $fixtureName);

        /** @var array $expected */
        $expected = include $phpFile;

        $item = new ScrapedItemBag($scraper, static::$url, '');
        $item->add($expected['item']);

        if (isset($expected['id'])) {
            $item->setOriginalId($expected['id']);
        }

        if (isset($expected['url'])) {
            $item->setOriginalUrl($expected['url']);
        }

        if (isset($expected['date'])) {
            $item->setDatetimeModified($expected['date']);
        }

        return $item;
    }
}
