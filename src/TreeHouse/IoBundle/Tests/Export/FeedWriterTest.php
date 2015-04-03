<?php

namespace TreeHouse\IoBundle\Tests\Export;

use Symfony\Component\Templating\EngineInterface;
use TreeHouse\IoBundle\Export\FeedWriter;

class FeedWriterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    protected $tmpFile;

    protected function setUp()
    {
        $this->tmpFile = tempnam(sys_get_temp_dir(), 'writer');
    }

    protected function tearDown()
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

        $writer->start($this->tmpFile);
        $writer->start($this->tmpFile);
    }

    public function testIsStarted()
    {
        $writer = $this->getWriter();

        $this->assertFalse($writer->isStarted());

        $writer->start($this->tmpFile);

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
            ['writeContent', ['some content']],
            ['writeItem', [$someItem = new \stdClass(), 'SomeTemplate']],
        ];
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testFinishThrowsExceptionWhenNotWriteStartCalled()
    {
        $writer = $this->getWriter();
        $writer->finish();
    }

    public function testWriteStart()
    {
        $writer = $this->getWriter();
        $writer->start($this->tmpFile);

        $this->assertContains('<feed>', file_get_contents($this->tmpFile), 'File contains rootNode');
    }

    public function testWriteEnd()
    {
        $writer = $this->getWriter();
        $writer->start($this->tmpFile);
        $writer->finish();

        $this->assertContains('</feed>', file_get_contents($this->tmpFile), 'File contains rootNode');
    }

    public function testWriteContent()
    {
        $writer = $this->getWriter();
        $writer->start($this->tmpFile);
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
        $writer->start($this->tmpFile);
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

        return new FeedWriter($templating, 'feed', 'item');
    }
}
