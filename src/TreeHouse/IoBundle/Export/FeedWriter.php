<?php

namespace TreeHouse\IoBundle\Export;

use Symfony\Component\Templating\EngineInterface;

class FeedWriter
{
    /**
     * @var EngineInterface
     */
    protected $templating;

    /**
     * @var null|resource
     */
    protected $pointer;

    /**
     * @var string
     */
    protected $rootNode;

    /**
     * @param EngineInterface $templating
     */
    public function __construct(EngineInterface $templating)
    {
        $this->templating = $templating;
    }

    /**
     * @param string $filename
     *
     * @throws \RuntimeException
     */
    public function open($filename)
    {
        if ($this->isOpen()) {
            throw new \RuntimeException('Writer is already open');
        }

        $this->pointer = fopen($filename, 'w');
    }

    /**
     * @return boolean
     */
    public function isOpen()
    {
        return is_resource($this->pointer);
    }

    /**
     * @throws \RuntimeException
     */
    public function close()
    {
        if (!$this->isOpen()) {
            throw new \RuntimeException('Writer is already closed');
        }

        fclose($this->pointer);

        $this->pointer = null;
    }

    /**
     * @param object $item
     * @param string $template
     *
     * @return string
     */
    public function renderItem($item, $template)
    {
        return $this->templating->render($template, ['item' => $item]);
    }

    /**
     * @param object $item
     * @param string $template
     *
     * @throws \RuntimeException
     */
    public function writeItem($item, $template)
    {
        $this->writeContent($this->renderItem($item, $template));
    }

    /**
     * @param string $content
     *
     * @throws \RuntimeException
     */
    public function writeContent($content)
    {
        if (!$this->isOpen()) {
            throw new \RuntimeException('Writer is not open');
        }

        fwrite($this->pointer, $content);
    }

    /**
     * Writes the end part of the XML, closing the rootnode.
     *
     * @throws \RuntimeException If open() is not called yet
     */
    public function writeEnd()
    {
        if (!$this->rootNode) {
            throw new \RuntimeException('Call writeStart() first');
        }

        $this->writeContent(sprintf('</%s>', $this->rootNode));

        $this->rootNode = null;
    }

    /**
     * Writes the start of the XML: containing the prolog, root node and legend as a comment block
     *
     * @param string $rootNode
     * @param string $namespaces
     */
    public function writeStart($rootNode, $namespaces = null)
    {
        $this->rootNode = $rootNode;

        // prolog
        $this->writeContent('<?xml version="1.0" encoding="UTF-8" ?>');

        // root node
        $this->writeContent(sprintf('<%s%s>', $this->rootNode, rtrim(' ' . $namespaces)));
    }
}
