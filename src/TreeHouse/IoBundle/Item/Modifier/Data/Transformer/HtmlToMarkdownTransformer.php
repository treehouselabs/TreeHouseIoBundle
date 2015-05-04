<?php

namespace TreeHouse\IoBundle\Item\Modifier\Data\Transformer;

use Markdownify\Converter;
use TreeHouse\Feeder\Exception\TransformationFailedException;
use TreeHouse\Feeder\Modifier\Data\Transformer\TransformerInterface;

class HtmlToMarkdownTransformer implements TransformerInterface
{
    /**
     * @var Converter
     */
    protected $converter;

    /**
     * @var \HTMLPurifier
     */
    protected $purifier;

    /**
     * @param Converter     $converter
     * @param \HTMLPurifier $purifier
     */
    public function __construct(Converter $converter, \HTMLPurifier $purifier)
    {
        $this->converter = $converter;
        $this->purifier  = $purifier;
    }

    /**
     * @inheritdoc
     */
    public function transform($value)
    {
        if (is_null($value)) {
            return $value;
        }

        if (is_scalar($value)) {
            $value = (string) $value;
        }

        if (!is_string($value)) {
            throw new TransformationFailedException(
                sprintf('Expected a string to transform, got %s instead', json_encode($value))
            );
        }

        // replace non-breaking spaces, somehow this results in a question mark when markdownifying
        $value = str_replace(['&nbsp;', "\xC2\xA0"], ' ', $value);

        // remove leading spaces/tabs
        $value = preg_replace('/^[ \t]+/m', '', $value);

        // purify the html first
        $value = $this->purifier->purify($value);

        // perform some replacements...
        $replacements = [
            [['/>\s+</', '/\s+<\//'],            ['><', '</']],   # remove whitespace/newlines between tags: this can cause
                                                                  # trailing whitespace after markdownifying
            [['/\s+<br\/?>/', '/<br\/?>\s+/'],   '<br>'],         # also remove whitespace/newlines around <br> tags
            ['/([^>])\n([^<])/',                 '\\1<br>\\2'],   # replace newlines with <br> if the newline is not between 2 tags
            ['/(<(p|li)>)<br\s?\/?>/i',          '\\1'],          # remove <br>'s at the beginning of a paragraph
            ['/<br\s?\/?>(<\/(p|li)>)/i',        '\\1'],          # remove <br>'s at the end of a paragraph
        ];

        foreach ($replacements as list($search, $replace)) {
            $value = preg_replace($search, $replace, $value);
        }

        // convert to markdown
        $value = @$this->converter->parseString($value);

        // Fix different types of bullets. What this does is check each line if it starts with any of "-×•○",
        // not followed by another bullet, and normalizes it to "* text".
        $value = preg_replace('/^[\-×•○]\s*([^\-×•○])/mu',  '* $1', $value);

        // Now make sure there's a newline before 2 consecutive lines that start with a bullet.
        // This could lead to superfluous newlines, but they will be corrected later on.
        $value = preg_replace('/(\n\* [^\n]+){2,}/', "\n$0", "\n".$value);

        // remove trailing spaces/tabs
        $value = preg_replace('/[ \t]+$/m', '', $value);

        // remove excessive newlines
        $value = preg_replace('/\n{3,}/m', "\n\n", $value);

        return trim($value);
    }
}
