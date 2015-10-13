<?php

namespace TreeHouse\IoBundle\Import;

use Symfony\Component\Filesystem\Filesystem;
use TreeHouse\IoBundle\Entity\Import;

class ImportStorage
{
    /**
     * @var string
     */
    protected $feedDir;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @param string     $feedDir
     * @param Filesystem $filesystem
     */
    public function __construct($feedDir, Filesystem $filesystem = null)
    {
        $this->feedDir = $feedDir;
        $this->filesystem = $filesystem ?: new Filesystem();
    }

    /**
     * @param Import $import
     *
     * @return string
     */
    public function getImportDir(Import $import)
    {
        return sprintf('%s/%s', $this->feedDir, $import->getId());
    }

    /**
     * @param Import $import
     */
    public function removeImport(Import $import)
    {
        $this->filesystem->remove($this->getImportDir($import));
    }
}
