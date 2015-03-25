<?php

namespace TreeHouse\IoBundle\Tests\Export;

use Symfony\Component\Templating\EngineInterface;
use TreeHouse\IoBundle\Export\FeedWriter;

class FeedWriterTest extends \PHPUnit_Framework_TestCase
{
    protected $tmpFile;

    public function setUp()
    {
        $this->tmpFile = tempnam(sys_get_temp_dir(), 'writer');
    }

    public function tearDown()
    {
        if (is_file($this->tmpFile)) {
            unlink($this->tmpFile);
        }
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testStartTwiceThrowsException()
    {
        $writer = $this->getWriter();

        $writer->start($this->tmpFile, 'feed', 'item');
        $writer->start($this->tmpFile, 'feed', 'item');
    }

    public function testIsStarted()
    {
        $writer = $this->getWriter();

        $this->assertFalse($writer->isStarted());

        $writer->start($this->tmpFile, 'feed', 'item');

        $this->assertTrue($writer->isStarted());
    }

    /**
     * @expectedException \RuntimeException
     * @dataProvider methodProvider
     */
    public function testMethodThrowsExceptionWhenNotStarted($method, $args = [])
    {
        $writer = $this->getWriter();

        call_user_func_array([$writer, $method], $args);
    }

    public function methodProvider()
    {
        return [
            ['finish'],
            ['writeStart', ['someRootNode']],
            ['writeEnd'],
            ['writeContent', ['some content']],
            ['writeItem', [$someItem = new \stdClass(), 'SomeTemplate']],
        ];
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testWriteEndThrowsExceptionWhenNotWriteStartCalled()
    {
        $writer = $this->getWriter();
        $writer->writeEnd();
    }

    public function testWriteStart()
    {
        $writer = $this->getWriter();
        $writer->start($this->tmpFile, 'feed', 'item');

        $this->assertEquals('', file_get_contents($this->tmpFile));

        $writer->writeStart();

        $this->assertContains('<feed>', file_get_contents($this->tmpFile), 'File contains rootNode');
    }

    public function testWriteEnd()
    {
        $writer = $this->getWriter();
        $writer->start($this->tmpFile, 'feed', 'item');

        $this->assertEquals('', file_get_contents($this->tmpFile));

        $writer->writeStart();
        $writer->writeEnd();

        $this->assertContains('</feed>', file_get_contents($this->tmpFile), 'File contains rootNode');
    }

    public function testWriteContent()
    {
        $writer = $this->getWriter();
        $writer->start($this->tmpFile, 'feed', 'item');

        $this->assertEquals('', file_get_contents($this->tmpFile));

        $writer->writeStart();
        $writer->writeContent('<someNode>some content</someNode>');

        $this->assertContains('<someNode>some content</someNode>', file_get_contents($this->tmpFile));
    }

    public function testWriteItem()
    {
        $someItem = new \stdClass();
        $template = 'SomeTemplate';
        $templateOutput = '<someNode>some rendered template</someNode>';

        $templating = $this->getMockBuilder(EngineInterface::class)->getMockForAbstractClass();
        $templating->expects($this->once())
            ->method('render')
            ->willReturn($templateOutput);

        $writer = $this->getWriter($templating);
        $writer->start($this->tmpFile, 'feed', 'item');

        $writer->writeStart('someRootNode');
        $writer->writeItem($someItem, $template);

        $this->assertContains($templateOutput, file_get_contents($this->tmpFile));
    }

    /**
     * @param null $templating
     *
     * @return FeedWriter
     */
    protected function getWriter($templating = null)
    {
        $templating = $templating ?: $this->getMockBuilder(EngineInterface::class)->getMockForAbstractClass();

        return new FeedWriter($templating);
    }
}
