<?php

namespace TreeHouse\IoBundle\Export;

use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\Templating\TemplateReferenceInterface;

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
     * @param string          $rootNode
     * @param string          $itemNode
     */
    public function __construct(EngineInterface $templating, $rootNode, $itemNode)
    {
        $this->templating = $templating;
        $this->rootNode = $rootNode;
        $this->itemNode = $itemNode;
    }

    /**
     * @return bool
     */
    public function isStarted()
    {
        return is_resource($this->pointer);
    }

    /**
     * @param string $filename
     * @param string $namespaces
     *
     * @throws \RuntimeException
     */
    public function start($filename, $namespaces = '')
    {
        if ($this->isStarted()) {
            throw new \RuntimeException('Writer has already started');
        }

        $this->pointer = fopen($filename, 'w');

        $this->writeStart($namespaces);
    }

    /**
     * @throws \RuntimeException
     */
    public function finish()
    {
        if (!$this->isStarted()) {
            throw new \RuntimeException('Writer has not yet started');
        }

        $this->writeEnd();

        fclose($this->pointer);

        $this->pointer = null;
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
     * @param object                            $item
     * @param string|TemplateReferenceInterface $template
     *
     * @throws \RuntimeException
     */
    public function writeItem($item, $template)
    {
        $this->writeContent($this->renderItem($item, $template));
    }

    /**
     * @param object                            $item
     * @param string|TemplateReferenceInterface $template
     *
     * @return string
     */
    public function renderItem($item, $template)
    {
        return $this->templating->render($template, ['item' => $item, 'itemNode' => $this->itemNode]);
    }

    /**
     * Writes the XML prolog.
     *
     * @param string $namespaces
     */
    protected function writeStart($namespaces = '')
    {
        $this->writeContent('<?xml version="1.0" encoding="UTF-8"?>');
        $this->writeContent(sprintf('<%s%s>', $this->rootNode, rtrim(' ' . $namespaces)));
    }

    /**
     * Writes the end part of the XML, closing the rootnode.
     *
     * @throws \RuntimeException If open() is not called yet
     */
    protected function writeEnd()
    {
        if (!$this->isStarted()) {
            throw new \RuntimeException('Writer has not yet started');
        }

        $this->writeContent(sprintf('</%s>', $this->rootNode));

        $this->rootNode = null;
    }
}
