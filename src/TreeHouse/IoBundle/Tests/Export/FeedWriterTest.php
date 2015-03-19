<?php

namespace TreeHouse\IoBundle\Tests\Export;

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
    public function testOpenTwiceThrowsException()
    {
        $writer = $this->getWriter();

        $writer->open($this->tmpFile);
        $writer->open($this->tmpFile);
    }

    public function testIsOpen()
    {
        $writer = $this->getWriter();

        $this->assertFalse($writer->isStarted());

        $writer->open($this->tmpFile);

        $this->assertTrue($writer->isStarted());
    }

    /**
     * @expectedException \RuntimeException
     * @dataProvider methodProvider
     */
    public function testMethodThrowsExceptionWhenNotOpen($method, $args = [])
    {
        $writer = $this->getWriter();

        call_user_func_array([$writer, $method], $args);
    }

    public function methodProvider()
    {
        return [
            ['close'],
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

        $writer->open($this->tmpFile);

        $this->assertEquals('', file_get_contents($this->tmpFile));

        $writer->writeStart('vacancies');

        $this->assertContains('<vacancies', file_get_contents($this->tmpFile), 'File contains rootNode');
    }

    public function testWriteEnd()
    {
        $writer = $this->getWriter();

        $writer->open($this->tmpFile);

        $this->assertEquals('', file_get_contents($this->tmpFile));

        $writer->writeStart('vacancies');
        $writer->writeEnd();

        $this->assertContains('</vacancies>', file_get_contents($this->tmpFile), 'File contains rootNode');
    }

    public function testWriteContent()
    {
        $writer = $this->getWriter();

        $writer->open($this->tmpFile);

        $this->assertEquals('', file_get_contents($this->tmpFile));

        $writer->writeStart('someRootNode');
        $writer->writeContent('<someNode>some content</someNode>');

        $this->assertContains('<someNode>some content</someNode>', file_get_contents($this->tmpFile));
    }

    public function testWriteItem()
    {
        $someItem = new \stdClass();
        $template = 'SomeTemplate';
        $templateOutput = '<someNode>some rendered template</someNode>';

        $templating = $this->getMockBuilder(
            'Symfony\Bundle\FrameworkBundle\Templating\EngineInterface'
        )->getMockForAbstractClass();

        $templating->expects($this->once())
            ->method('render')
            ->with($template, ['item' => $someItem])
            ->willReturn($templateOutput);

        $writer = $this->getWriter($templating);

        $writer->open($this->tmpFile);

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
        $templating = $templating ?: $this->getMockBuilder(
            'Symfony\Bundle\FrameworkBundle\Templating\EngineInterface'
        )->getMockForAbstractClass();

        $writer = new FeedWriter($templating);

        return $writer;
    }
}
