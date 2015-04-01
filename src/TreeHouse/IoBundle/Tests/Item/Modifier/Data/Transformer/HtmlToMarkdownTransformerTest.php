<?php

namespace TreeHouse\IoBundle\Tests\Item\Modifier\Data\Transformer;

use Markdownify\ConverterExtra;
use Symfony\Component\Finder\Finder;
use TreeHouse\IoBundle\Item\Modifier\Data\Transformer\HtmlToMarkdownTransformer;

class HtmlToMarkdownTransformerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider transformDataProvider
     */
    public function testTransform($markdownPath, $htmlPath)
    {
        $markdownify = new ConverterExtra(false, false, false);
        $purifier    = new \HTMLPurifier($this->getPurifierConfig());

        $transformer = new HtmlToMarkdownTransformer($markdownify, $purifier);

        $html = trim(file_get_contents($htmlPath));
        $expected = trim(file_get_contents($markdownPath));

        $actual = $transformer->transform($html);

        $this->assertEquals($expected, $actual);
    }

    public function transformDataProvider()
    {
        $finder = new Finder();
        $finder->in(__DIR__.'/fixtures')->name('*.md');

        $retval = [];

        /** @var \SplFileInfo $file */
        foreach ($finder as $file) {
            $markdownPath = $file->getPathName();
            $ext = strrchr($markdownPath, '.');
            $htmlPath = preg_replace('/'.preg_quote($ext, '/').'$/i', '.html', $markdownPath);

            $retval[] = [$markdownPath, $htmlPath];
        }

        return $retval;
    }

    /**
     * @return array
     */
    protected function getPurifierConfig()
    {
        return [
            'Attr.AllowedClasses'                     => [],
            'AutoFormat.AutoParagraph'                => true,
            'AutoFormat.RemoveEmpty'                  => true,
            'AutoFormat.RemoveEmpty.RemoveNbsp'       => true,
            'AutoFormat.RemoveSpansWithoutAttributes' => true,
            'Core.RemoveProcessingInstructions'       => true,
            'Cache.SerializerPermissions'             => 0775,
            'HTML.Allowed'                            => 'div,p,span,br,em,strong,b,i,small,cite,blockquote,q,code,var,samp,kbd,dfn,abbr,sup,sub,h1,h2,h3,ul,li',
            'HTML.Doctype'                            => 'HTML 4.01 Strict',
        ];
    }
}
