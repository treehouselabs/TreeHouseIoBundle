<?php

namespace TreeHouse\IoBundle\Export;

use Doctrine\Common\Util\ClassUtils as DoctrineClassUtils;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Filesystem;
use TreeHouse\IoBundle\Event\ExportFeedEvent;
use TreeHouse\IoBundle\Event\ExportProgressEvent;
use TreeHouse\IoBundle\Export\FeedType\AbstractFeedType;

class FeedExporter
{
    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var string
     */
    protected $cacheDir;

    /**
     * @var string
     */
    protected $exportDir;

    /**
     * @var FeedWriter
     */
    protected $writer;

    /**
     * @var AbstractFeedType[]
     */
    protected $types = [];

    /**
     * @param string                   $cacheDir
     * @param string                   $exportDir
     * @param FeedWriter               $writer
     * @param Filesystem               $filesystem
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct($cacheDir, $exportDir, FeedWriter $writer, Filesystem $filesystem, EventDispatcherInterface $dispatcher = null)
    {
        $this->cacheDir   = $cacheDir;
        $this->exportDir  = $exportDir;
        $this->writer     = $writer;
        $this->filesystem = $filesystem;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param object $item
     * @param array  $types
     *
     * @return bool
     */
    public function cacheItem($item, array $types = [])
    {
        if (false === $this->supports($item)) {
            return false;
        }

        if (empty($types)) {
            $types = $this->getTypesForItem($item);
        }

        foreach ($types as $type) {
            $name      = $type->getName();
            $template  = $type->getTemplate();
            $ttl       = $type->getTtl();
            $cacheFile = $this->getItemCacheFilename($item, $name);

            if (!file_exists($cacheFile) || !$this->isFresh($cacheFile, $ttl)) {
                $this->filesystem->mkdir(dirname($cacheFile));

                $xml = $this->writer->renderItem($item, $template);

                $this->filesystem->dumpFile($cacheFile, $xml, null);
            }
        }

        return true;
    }

    /**
     * Clears cached exports for an item
     *
     * @param    object   $item
     * @param array $types
     */
    public function clearCache($item, array $types = [])
    {
        if (false === $this->supports($item)) {
            return;
        }

        if (empty($types)) {
            $types = $this->getTypesForItem($item);
        }

        foreach ($types as $type) {
            $cacheFile = $this->getItemCacheFilename($item, $type->getName());
            $this->filesystem->remove($cacheFile);
        }
    }

    /**
     * @param AbstractFeedType $type
     * @param bool             $force
     *
     * @return bool
     */
    public function exportFeed(AbstractFeedType $type, $force = false)
    {
        $name       = $type->getName();
        $rootNode   = $type->getRootNode();
        $namespaces = $type->getNamespaces();
        $qb         = $type->getQueryBuilder('x');
        $ttl        = $type->getTtl();
        $numResults = $this->getNumberOfResults($qb);
        $query      = $qb->getQuery();
        $file       = $this->getFeedFilename($name, false);
        $gzFile     = $this->getFeedFilename($name, true);
        $tmpFile    = tempnam($this->cacheDir, 'io_feed').'.xml';
        $gzTmpFile  = $tmpFile.'.gz';

        // check if we are up-to-date
        if (false === $force && file_exists($file) && $this->isFresh($file, $ttl)) {
            return false;
        }

        $this->dispatch(ExportEvents::PRE_EXPORT_FEED, new ExportFeedEvent($file, $type, $numResults));

        $this->filesystem->mkdir(dirname($file));

        $this->writer->open($tmpFile);

        $this->writer->writeStart($rootNode, $namespaces);

        $num = 0;

        foreach ($query->iterate() as list($item)) {
            $this->dispatch(
                ExportEvents::PRE_EXPORT_ITEM,
                new ExportProgressEvent($num, $numResults)
            );

            $cacheFile = $this->getItemCacheFilename($item, $name);

            $this->cacheItem($item, [$type]);

            $this->writer->writeContent(file_get_contents($cacheFile));

            $this->dispatch(
                ExportEvents::POST_EXPORT_ITEM,
                new ExportProgressEvent($num, $numResults)
            );

            if ($num++ % 2000 === 0) {
                $this->pingDatabase($qb->getEntityManager());
            }
        }

        $this->writer->writeEnd();

        $this->writer->close();

        $this->gzip($tmpFile, $gzTmpFile);
        rename($tmpFile, $file);
        rename($gzTmpFile, $gzFile);

        $this->dispatch(ExportEvents::POST_EXPORT_FEED, new ExportFeedEvent($file, $type, $numResults));

        return true;
    }

    /**
     * @param AbstractFeedType $type
     */
    public function registerType(AbstractFeedType $type)
    {
        $this->types[] = $type;
    }

    /**
     * @param object $item
     *
     * @return bool
     */
    public function supports($item)
    {
        foreach ($this->types as $type) {
            if ($type->supports($item)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $name
     *
     * @return AbstractFeedType|null
     */
    public function getType($name)
    {
        foreach ($this->types as $type) {
            if ($type->getName() === $name) {
                return $type;
            }
        }

        return null;
    }

    /**
     * @param string $type
     *
     * @return bool
     */
    public function hasType($type)
    {
        foreach ($this->types as $typeObj) {
            if ($typeObj->getName() === $type) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return AbstractFeedType[]
     */
    public function getTypes()
    {
        return $this->types;
    }

    /**
     * @return EventDispatcherInterface
     */
    public function getDispatcher()
    {
        return $this->dispatcher;
    }

    /**
     * @param string $name
     * @param bool   $gzip
     *
     * @return string
     */
    public function getFeedFilename($name, $gzip)
    {
        $path = [
            $this->exportDir,
            sprintf('%s.%s', $name, ($gzip ? 'xml.gz' : 'xml')),
        ];

        return implode(DIRECTORY_SEPARATOR, $path);
    }

    /**
     * @param object $item
     *
     * @return AbstractFeedType[]
     */
    protected function getTypesForItem($item)
    {
        $types = [];

        foreach ($this->types as $type) {
            if ($type->supports($item)) {
                $types[] = $type;
            }
        }

        return $types;
    }

    /**
     * @param string $file
     * @param int    $ttl  time to life in minutes
     *
     * @return boolean
     */
    protected function isFresh($file, $ttl)
    {
        $maxAge = new \DateTime(sprintf('-%d minutes', $ttl));

        if (!file_exists($file)) {
            return false;
        }

        return (filemtime($file) > $maxAge->getTimestamp());
    }

    /**
     * @param object $item
     * @param string $name
     *
     * @return string
     */
    protected function getItemCacheFilename($item, $name)
    {
        $class = DoctrineClassUtils::getClass($item);

        $hash = hash('crc32b', sprintf('%s-%d', $class, $item->getId()));
        $path = [
            $this->cacheDir,
            $hash{0},
            $hash{1},
            $hash{2},
            substr($hash, 3),
            sprintf('%s.xml', $name),
        ];

        return implode(DIRECTORY_SEPARATOR, $path);
    }

    /**
     * @param QueryBuilder $builder
     *
     * @return integer
     */
    protected function getNumberOfResults(QueryBuilder $builder)
    {
        $countQb = clone $builder;

        // remove some parts which are not needed in the count query, but could slow it down
        foreach (['groupBy', 'orderBy'] as $field) {
            if ($countQb->getDQLPart($field)) {
                $countQb->resetDQLPart($field);
            }
        }

        if (null !== $countQb->getMaxResults()) {
            return $countQb->getMaxResults();
        }

        $aliases = $countQb->getRootAliases();
        $rootAlias = reset($aliases);

        $query = $countQb->select('COUNT('.$rootAlias.')')->getQuery();

        return (int) $query->getSingleScalarResult();
    }

    /**
     * pings database to keep connection alive
     *
     * @param EntityManager $manager
     */
    protected function pingDatabase($manager)
    {
        $tmp = $manager->createNativeQuery(
            'SELECT 1',
            new ResultSetMapping()
        )->getResult();

        unset($tmp);

        $manager->clear();
    }

    /**
     * Encodes a file using gzip compression
     *
     * @param string $source      The source file
     * @param string $destination The encoded destination file
     */
    protected function gzip($source, $destination)
    {
        $fp = fopen($source, 'r');
        $zp = gzopen($destination, 'wb9');

        while (!feof($fp)) {
            gzwrite($zp, fgets($fp));
        }

        fclose($fp);
        gzclose($zp);
    }

    /**
     * @param string $eventName
     * @param Event  $event
     */
    protected function dispatch($eventName, Event $event = null)
    {
        if ($this->dispatcher) {
            $this->dispatcher->dispatch($eventName, $event);
        }
    }
}
