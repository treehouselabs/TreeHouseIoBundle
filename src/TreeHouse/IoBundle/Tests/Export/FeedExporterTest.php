<?php

namespace TreeHouse\IoBundle\Tests\Export;

use Symfony\Component\Templating\TemplateReference;
use Symfony\Component\Filesystem\Filesystem;
use TreeHouse\IoBundle\Export\FeedExporter;
use TreeHouse\IoBundle\Export\FeedType\FeedTypeInterface;
use TreeHouse\IoBundle\Export\FeedWriter;
use TreeHouse\IoBundle\Export\FeedWriterFactory;

class FeedExporterTest extends \PHPUnit_Framework_TestCase
{
    protected $tmpDir;

    public function setUp()
    {
        $this->tmpDir = sys_get_temp_dir().'/exporter';

        $filesystem = new Filesystem();
        $filesystem->mkdir($this->tmpDir);
    }

    public function tearDown()
    {
        if (is_dir($this->tmpDir)) {
            $fs = new Filesystem();
            $fs->remove($this->tmpDir);
        }
    }

    public function testRegisterType()
    {
        $exporter = $this->getExporter();

        $type = $this->getMockBuilder(FeedTypeInterface::class)->getMockForAbstractClass();
        $type
            ->expects($this->any())
            ->method('getName')
            ->willReturn('some_type')
        ;
        $type
            ->expects($this->any())
            ->method('supports')
            ->willReturn(true)
        ;

        $exporter->registerType($type, 'some_type');

        $this->assertTrue($exporter->hasType('some_type'));
        $this->assertEquals(['some_type' => $type], $exporter->getTypes());
        $this->assertEquals($type, $exporter->getType('some_type'));
    }

    public function testCacheItemWritesAFileToDisk()
    {
        $writer = $this
            ->getMockBuilder(FeedWriter::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $writer
            ->expects($this->any())
            ->method('renderItem')
            ->willReturn('<someNode>some item data</someNode>')
        ;

        $item = $this->getMockBuilder('stdClass')->setMethods(['getId'])->getMock();
        $item->expects($this->any())
            ->method('getId')
            ->willReturn(234)
        ;

        $exporter = $this->getExporter(null, null, $writer);

        $type = $this->getMockForAbstractClass(FeedTypeInterface::class);
        $type
            ->expects($this->any())
            ->method('getName')
            ->willReturn('some_type')
        ;
        $type
            ->expects($this->any())
            ->method('supports')
            ->willReturn(true)
        ;

        $exporter->registerType($type, 'some_type');

        $cachedFile = $exporter->getItemCacheFilename($item, $type);

        if (file_exists($cachedFile)) {
            unlink($cachedFile);
        }

        $exporter->cacheItem($item);
        $this->assertTrue(file_exists($cachedFile));

        // overwrite cache file with new data
        file_put_contents($cachedFile, 'test');

        // caching now will leave the file alone
        $exporter->cacheItem($item);
        $this->assertEquals('test', file_get_contents($cachedFile));

        // caching with force will overwrite the file
        $exporter->cacheItem($item, [], true);
        $this->assertEquals('<someNode>some item data</someNode>', file_get_contents($cachedFile));
    }

    /**
     * Tests that the item cache filename reflects different aspects of the used template and type
     */
    public function testCacheItemFilenameReflectsTemplate()
    {
        /** @var FeedExporter $exporter */
        $exporter = $this
            ->getMockBuilder(FeedExporter::class)
            ->disableOriginalConstructor()
            ->setMethods(['cacheItem'])
            ->getMock()
        ;

        $item = $this->getMockBuilder('stdClass')->setMethods(['getId'])->getMock();
        $item->expects($this->any())
            ->method('getId')
            ->willReturn(234)
        ;

        $templateFile = tempnam(sys_get_temp_dir(), 'template');

        /** @var FeedTypeInterface[] $types */
        $types = [];

        // load a basic type
        $types[] = $this->createFeedType('type1');
        $types[] = $this->createFeedType('type2', 'root2');
        $types[] = $this->createFeedType('type3', null, 'item2');
        $types[] = $this->createFeedType('type4', null, null, 'template2');
        $types[] = $this->createFeedType('type5', null, null, new TemplateReference($templateFile));

        $files = [];
        foreach ($types as $type) {
            $exporter->registerType($type, $type->getName());
            $files[] = $file = $exporter->getItemCacheFilename($item, $type);

            // assert that the item cache filename is a string
            $this->assertInternalType('string', $file);
        }

        // now change the template data and the cached file should be different
        file_put_contents($templateFile, 'template_data_changed');
        $types[] = $type = $this->createFeedType('type6', null, null, new TemplateReference($templateFile));
        $exporter->registerType($type, $type->getName());
        $files[] = $exporter->getItemCacheFilename($item, $type);

        // assert that the files are all unique
        $this->assertSame(sizeof($files), sizeof(array_unique($files)));
    }

    /**
     * @param string $name
     * @param string $rootNode
     * @param string $itemNode
     * @param string $template
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|FeedTypeInterface
     */
    protected function createFeedType($name, $rootNode = null, $itemNode = null, $template = null)
    {
        $type = $this->getMockForAbstractClass(FeedTypeInterface::class);
        $type->expects($this->any())->method('getName')->willReturn($name);
        $type->expects($this->any())->method('getRootNode')->willReturn($rootNode ?: 'feed');
        $type->expects($this->any())->method('getItemNode')->willReturn($itemNode ?: 'item');
        $type->expects($this->any())->method('getTemplate')->willReturn($template ?: 'AppBundle:Feed:export.xml.twig');

        return $type;
    }

    /**
     * @param null|string     $cacheDir
     * @param null            $exportDir
     * @param null|FeedWriter $writer
     *
     * @return FeedExporter
     */
    protected function getExporter($cacheDir = null, $exportDir = null, $writer = null)
    {
        if (!$writer) {
            $writer = $this->getMockBuilder(FeedWriter::class)->disableOriginalConstructor()->getMock();
        }

        $writerFactory = $this
            ->getMockBuilder(FeedWriterFactory::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $writerFactory
            ->expects($this->any())
            ->method('createWriter')
            ->willReturn($writer)
        ;

        return new FeedExporter(
            $cacheDir ?: $this->tmpDir,
            $exportDir ?: $this->tmpDir,
            $writerFactory,
            new Filesystem()
        );
    }
}
