<?php

namespace TreeHouse\IoBundle\Tests\Import\FeedType;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use TreeHouse\Feeder\Event\InvalidItemEvent;
use TreeHouse\Feeder\Event\ItemNotModifiedEvent;
use TreeHouse\Feeder\FeedEvents;
use TreeHouse\Feeder\Reader\ReaderInterface;
use TreeHouse\IoBundle\Entity\Feed;
use TreeHouse\IoBundle\Import\Feed\FeedBuilder;
use TreeHouse\IoBundle\Import\Feed\FeedItemBag;
use TreeHouse\IoBundle\Import\Feed\TransportFactory;
use TreeHouse\IoBundle\Import\ImportRegistry;
use TreeHouse\IoBundle\Import\Reader\ReaderBuilder;
use TreeHouse\IoBundle\Item\ItemBag;
use TreeHouse\IoBundle\Tests\Item\ItemFixture;
use TreeHouse\IoBundle\Tests\TestCase;

/**
 * AbstractFeedTypeTest is the base class for a functional feed type test.
 * It doesn't contain any tests itself, only the basic functionality to
 * test a feed type with fixtures.
 *
 * If you want to test a feed type, extend {@link DefaultFeedTypeTest} instead.
 */
abstract class AbstractFeedTypeTest extends TestCase
{
    /**
     * @var string[]
     */
    protected $loadedFixtures = [];

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->loadedFixtures = [];

        parent::setUp();
    }

    /**
     * Removes all downloaded fixtures (they're saved in tmp folder).
     */
    protected function tearDown()
    {
        foreach ($this->loadedFixtures as $fixture) {
            unlink($fixture);
        }

        parent::tearDown();
    }

    /**
     * @param ItemFixture $fixture
     */
    protected function assertOriginalId(ItemFixture $fixture)
    {
        $this->assertEquals(
            $this->getOriginalId($fixture->getExpectedItem()),
            $this->getOriginalId($fixture->getActualItem())
        );
    }

    /**
     * @param ItemFixture $fixture
     */
    protected function assertOriginalUrl(ItemFixture $fixture)
    {
        $this->assertEquals(
            $this->getOriginalUrl($fixture->getExpectedItem()),
            $this->getOriginalUrl($fixture->getActualItem())
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
        $actual = $fixture->getActualItem()->all();

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
     * Asserts a value.
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
     * Normalizes values before asserting them.
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
     * @param string $name     The fixture name
     * @param string $feedType The feed type
     *
     * @throws \RuntimeException
     *
     * @return ItemFixture
     */
    protected function getItemFixture($name, $feedType)
    {
        if (null === $feed = $this->getFeed($feedType)) {
            $this->markTestSkipped(sprintf('Add an origin with a %s feed to the database first', $feedType));
        }

        $actual = $this->getActualItemFixture($feed, $name, $feedType);
        $expected = $this->getExpectedItemFixture($feed, $name, $feedType);

        return new ItemFixture($actual, $expected);
    }

    /**
     * @param Feed   $feedEntity
     * @param string $name       The fixture name
     * @param string $feedType   The feed type
     *
     * @throws \RuntimeException
     *
     * @return ItemBag
     */
    protected function getActualItemFixture(Feed $feedEntity, $name, $feedType)
    {
        $type = $this->getImportRegistry()->getFeedType($feedType);

        $dispatcher = $this->createEventDispatcher();

        $options = $this->getReaderTypeOptions($feedEntity);
        $reader = $this->createReader($feedEntity, $name, $dispatcher, $options);

        $builder = new FeedBuilder($dispatcher);
        $options = $this->getFeedTypeOptions($feedEntity);
        $feed = $builder->build($type, $reader, $options);

        if (null === $item = $feed->getNextItem()) {
            throw new \RuntimeException('Expecting a non-filtered feed item, got nothing.');
        }

        return $item;
    }

    /**
     * @param Feed   $feed
     * @param string $name
     * @param string $feedType
     *
     * @return FeedItemBag
     */
    protected function getExpectedItemFixture(Feed $feed, $name, $feedType)
    {
        $refl = new \ReflectionClass(get_class($this));
        $phpFile = sprintf('%s/fixtures/%s/%s.php', dirname($refl->getFilename()), $feedType, $name);

        /** @var array $expected */
        $expected = include $phpFile;

        $item = new FeedItemBag($feed, $expected['id'], $expected['item']);

        if (isset($expected['url'])) {
            $item->setOriginalUrl($expected['url']);
        }

        if (isset($expected['date'])) {
            $item->setDatetimeModified($expected['date']);
        }

        return $item;
    }

    /**
     * Returns a feed entity for a specific feed type.
     *
     * @param string $type
     *
     * @return mixed
     */
    protected function getFeed($type)
    {
        return $this
            ->getEntityManager()
            ->createQuery('SELECT f, o FROM TreeHouseIoBundle:Feed f JOIN f.origin o WHERE f.type = :type')
            ->setParameter('type', $type)
            ->setMaxResults(1)
            ->getOneOrNullResult()
        ;
    }

    /**
     * @return EventDispatcherInterface
     */
    protected function createEventDispatcher()
    {
        $dispatcher = new EventDispatcher();

        $dispatcher->addListener(FeedEvents::ITEM_FILTERED, function (ItemNotModifiedEvent $e) {
            throw new \RuntimeException(sprintf('Feed item filtered, reason: %s', $e->getReason()));
        });

        $dispatcher->addListener(FeedEvents::ITEM_FAILED, function (ItemNotModifiedEvent $e) {
            throw new \RuntimeException(sprintf('Item modification failed, reason: %s', $e->getReason()));
        });

        $dispatcher->addListener(FeedEvents::ITEM_INVALID, function (InvalidItemEvent $e) {
            throw new \RuntimeException(sprintf('Invalid item, reason: %s', $e->getReason()));
        });

        return $dispatcher;
    }

    /**
     * @param Feed                     $feed
     * @param string                   $fixtureName
     * @param EventDispatcherInterface $dispatcher
     * @param array                    $options
     *
     * @return ReaderInterface
     */
    protected function createReader(Feed $feed, $fixtureName, EventDispatcherInterface $dispatcher, array $options = [])
    {
        $readerType = $this->getImportRegistry()->getReaderType($feed->getReaderType());

        $refl = new \ReflectionClass(get_class($this));
        $xml = sprintf('%s/fixtures/%s/%s.xml', dirname($refl->getFileName()), $feed->getType(), $fixtureName);

        $transportConfig = TransportFactory::createConfigFromFile($xml);

        $builder = new ReaderBuilder($dispatcher, sys_get_temp_dir() . '/' . $feed->getType());

        return $builder->build($readerType, $transportConfig, $builder::RESOURCE_TYPE_MAIN, $options);
    }

    /**
     * @return ImportRegistry
     */
    protected function getImportRegistry()
    {
        return $this->get('tree_house.io.import.registry');
    }

    /**
     * @param ItemBag $item
     *
     * @return string
     */
    protected function getOriginalId(ItemBag $item)
    {
        return $item->getOriginalId();
    }

    /**
     * @param ItemBag $item
     *
     * @return string
     */
    protected function getOriginalUrl(ItemBag $item)
    {
        return $item->getOriginalUrl();
    }

    /**
     * @param Feed $feed
     *
     * @return array
     */
    protected function getFeedTypeOptions(Feed $feed)
    {
        return array_merge(
            [
                'forced' => true,
                'feed' => $feed,
                'default_values' => $feed->getDefaultValues(),
            ],
            $feed->getOptions()
        );
    }

    /**
     * @param Feed $feed
     *
     * @return array
     */
    protected function getReaderTypeOptions(Feed $feed)
    {
        return array_merge(
            [
                'partial' => $feed->isPartial(),
                'forced' => true,
            ],
            $feed->getReaderOptions()
        );
    }
}
