<?php

namespace TreeHouse\IoBundle\Test\Import\FeedType;

use Symfony\Component\Finder\Finder;

/**
 * The default class to test a feed type using fixtures. The test is set up to
 * automatically search for fixtures for the tested feed type.
 *
 * Usage:
 *
 * Extend this class in your bundle, eg: AcmeFeedTypeTest for feed type "acme".
 * Then create a "fixtures" directory in the same directory where AcmeFeedTypeTest
 * is, and inside that an "acme" directory. Now for every xml file found, the test
 * expects a similar named php file that returns an array for the expected item.
 *
 * The example structure:
 *
 * <pre>
 *   Tests
 *   + Import
 *     + FeedType
 *       - AcmeFeedTypeTest.php
 *       + fixtures
 *         + acme
 *           - regular.php
 *           - regular.xml
 * </pre>
 *
 * Where the regular.php fixture looks like this:
 *
 * <code>
 *   return [
 *     'id'   => '123ab',                     // required
 *     'url'  => 'http://example.org',        // optional
 *     'date' => '2014-01-20T12:34:56+00:00', // optional
 *     'item' => [                            // required
 *       'foo' => 'bar',
 *       [...]
 *     ]
 *   ];
 * </code>
 */
abstract class FeedTypeTestCase extends AbstractFeedTypeTestCase
{
    /**
     * @return string
     */
    abstract protected function getFeedType();

    /**
     * @return string[]
     */
    public function getFixtureNames()
    {
        $refl = new \ReflectionClass(get_class($this));
        $dir = sprintf('%s/fixtures/%s', dirname($refl->getFilename()), $this->getFeedType());

        $files = Finder::create()->files()->name('*.xml')->in($dir);

        $fixtures = [];
        /** @var \SplFileInfo $file */
        foreach ($files as $file) {
            $fixtures[] = [$file->getBasename('.xml')];
        }

        if (empty($fixtures)) {
            $this->markTestSkipped(sprintf('No fixtures for %s created', $this->getFeedType()));
        }

        return $fixtures;
    }

    /**
     * @dataProvider getFixtureNames
     *
     * @param string $name
     */
    public function testFixtures($name)
    {
        $this->assertFixture($this->getItemFixture($name, $this->getFeedType()));
    }
}
