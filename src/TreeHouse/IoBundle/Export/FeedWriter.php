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
     * @var string
     */
    protected $rootNode;

    /**
     * @var string
     */
    protected $itemNode;

    /**
     * @var null|resource
     */
    protected $pointer;

    /**
     * @param EngineInterface $templating
     */
    public function __construct(EngineInterface $templating)
    {
        $this->templating = $templating;
    }

    /**
     * @param string $filename
     * @param string $rootNode
     * @param string $itemNode
     *
     * @throws \RuntimeException
     */
    public function start($filename, $rootNode, $itemNode)
    {
        if ($this->isStarted()) {
            throw new \RuntimeException('Writer has already started');
        }

        $this->rootNode = $rootNode;
        $this->itemNode = $itemNode;

        $this->pointer = fopen($filename, 'w');
    }

    /**
     * @return boolean
     */
    public function isStarted()
    {
        return is_resource($this->pointer);
    }

    /**
     * @throws \RuntimeException
     */
    public function finish()
    {
        if (!$this->isStarted()) {
            throw new \RuntimeException('Writer has not yet started');
        }

        fclose($this->pointer);

        $this->pointer = null;
    }

    /**
     * Writes the XML prolog
     *
     * @param string $namespaces
     */
    public function writeStart($namespaces = '')
    {
        $this->writeContent('<?xml version="1.0" encoding="UTF-8"?>');
        $this->writeContent(sprintf('<%s%s>', $this->rootNode, rtrim(' ' . $namespaces)));
    }

    /**
     * Writes the end part of the XML, closing the rootnode.
     *
     * @throws \RuntimeException If open() is not called yet
     */
    public function writeEnd()
    {
        if (!$this->isStarted()) {
            throw new \RuntimeException('Writer has not yet started');
        }

        $this->writeContent(sprintf('</%s>', $this->rootNode));

        $this->rootNode = null;
    }

    /**
     * @param string $content
     *
     * @throws \RuntimeException
     */
    public function writeContent($content)
    {
        if (!$this->isStarted()) {
            throw new \RuntimeException('Writer has not yet started');
        }

        fwrite($this->pointer, $content);
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
     * @param object $item
     * @param string $template
     *
     * @return string
     */
    public function renderItem($item, $template)
    {
        return $this->templating->render($template, ['item' => $item, 'itemNode' => $this->itemNode]);
    }
}
