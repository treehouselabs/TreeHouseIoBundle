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
use TreeHouse\IoBundle\Export\FeedType\FeedTypeInterface;

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
     * @var FeedTypeInterface[]
     */
    protected $types = [];

    /**
     * @var array
     */
    protected $templateHashes = [];

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
            $template  = $type->getTemplate();
            $ttl       = $type->getTtl();
            $cacheFile = $this->getItemCacheFilename($item, $type);

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
     * @param object $item
     * @param array  $types
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
            $cacheFile = $this->getItemCacheFilename($item, $type);
            $this->filesystem->remove($cacheFile);
        }
    }

    /**
     * @param FeedTypeInterface $type
     * @param bool              $force
     *
     * @return bool
     */
    public function exportFeed(FeedTypeInterface $type, $force = false)
    {
        $file      = $this->getFeedFilename($type, false);
        $gzFile    = $this->getFeedFilename($type, true);
        $tmpFile   = $this->getFeedCacheFilename($type);
        $gzTmpFile = $tmpFile . '.gz';

        // check if we are up-to-date
        if (false === $force && file_exists($file) && $this->isFresh($file, $type->getTtl())) {
            return false;
        }

        $qb    = $type->getQueryBuilder('x');
        $count = $this->getNumberOfResults($qb);

        $this->dispatch(ExportEvents::PRE_EXPORT_FEED, new ExportFeedEvent($file, $type, $count));

        $this->filesystem->mkdir(dirname($file));

        $this->writer->start($tmpFile, $type->getRootNode(), $type->getItemNode());
        $this->writer->writeStart($this->getNamespaceAttributes($type));

        $num = 0;
        foreach ($qb->getQuery()->iterate() as list($item)) {
            $this->dispatch(
                ExportEvents::PRE_EXPORT_ITEM,
                new ExportProgressEvent($num, $count)
            );

            $this->cacheItem($item, [$type]);

            $cacheFile = $this->getItemCacheFilename($item, $type);
            $this->writer->writeContent(file_get_contents($cacheFile));

            $this->dispatch(ExportEvents::POST_EXPORT_ITEM, new ExportProgressEvent($num, $count));

            if ($num++ % 2000 === 0) {
                $this->pingDatabase($qb->getEntityManager());
            }
        }

        $this->writer->writeEnd();
        $this->writer->finish();

        $this->gzip($tmpFile, $gzTmpFile);
        rename($tmpFile, $file);
        rename($gzTmpFile, $gzFile);

        $this->dispatch(ExportEvents::POST_EXPORT_FEED, new ExportFeedEvent($file, $type, $count));

        return true;
    }

    /**
     * @param FeedTypeInterface $type
     * @param string            $alias
     */
    public function registerType(FeedTypeInterface $type, $alias)
    {
        $this->types[$alias] = $type;
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
     * @return FeedTypeInterface
     *
     * @throws \OutOfBoundsException when the type is not registered
     */
    public function getType($name)
    {
        if (array_key_exists($name, $this->types)) {
            return $this->types[$name];
        }

        throw new \OutOfBoundsException(
            sprintf(
                'Export type "%s" is not supported. You can add it by creating a service which implements %s, '.
                'and tag it with tree_house.io.export.feed_type',
                $name,
                FeedTypeInterface::class
            )
        );
    }

    /**
     * @param string $name
     *
     * @return boolean
     */
    public function hasType($name)
    {
        return array_key_exists($name, $this->types);
    }

    /**
     * @return FeedTypeInterface[]
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
     * Returns the location of the generated feed file. This is the location where the definitive
     * exported feed will be cached and served from.
     *
     * @param FeedTypeInterface $type
     * @param bool              $gzip
     *
     * @return string
     */
    public function getFeedFilename(FeedTypeInterface $type, $gzip = false)
    {
        $path = [
            $this->exportDir,
            sprintf('%s.%s', $type->getName(), ($gzip ? 'xml.gz' : 'xml')),
        ];

        return implode(DIRECTORY_SEPARATOR, $path);
    }

    /**
     * Returns the location of the feed file to export. This is the location where the actual
     * exporting will take place and where all the separate listing XML files are cached.
     *
     * @param FeedTypeInterface $type
     * @param boolean           $gzip
     *
     * @return string
     */
    public function getFeedCacheFilename(FeedTypeInterface $type, $gzip = false)
    {
        $path = [
            $this->cacheDir,
            sprintf('%s.%s', $type->getName(), ($gzip ? 'xml.gz' : 'xml')),
        ];

        return implode(DIRECTORY_SEPARATOR, $path);
    }

    /**
     * @param object            $item
     * @param FeedTypeInterface $type
     *
     * @return string
     */
    public function getItemCacheFilename($item, FeedTypeInterface $type)
    {
        $class = DoctrineClassUtils::getClass($item);

        $hash = hash('crc32b', sprintf('%s-%d', $class, $item->getId()));
        $path = [
            $this->cacheDir,
            $hash{0},
            $hash{1},
            $hash{2},
            substr($hash, 3),
            sprintf('%s.xml', $this->getTemplateHash($type)),
        ];

        return implode(DIRECTORY_SEPARATOR, $path);
    }

    /**
     * @param object $item
     *
     * @return FeedTypeInterface[]
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
     * @param FeedTypeInterface $type
     *
     * @return null|string
     */
    protected function getNamespaceAttributes(FeedTypeInterface $type)
    {
        $namespaces = $type->getNamespaces();

        if (empty($namespaces)) {
            return null;
        }

        $str = '';
        foreach ($namespaces as $name => $schemaLocation) {
            $str .= sprintf(
                'xmlns="%s" xmlns:xsi="%s" xsi:schemaLocation="%s %s" ',
                $name,
                'http://www.w3.org/2001/XMLSchema-instance',
                $name,
                $schemaLocation
            );
        }

        return trim($str);
    }

    /**
     * @param FeedTypeInterface $type
     *
     * @return string
     */
    protected function getTemplateHash(FeedTypeInterface $type)
    {
        if (!array_key_exists($type->getName(), $this->templateHashes)) {
            // TODO find a way to get the template contents, so the hash changes when the template does
            $hash = md5($type->getItemNode() . $type->getTemplate());
            $this->templateHashes[$type->getName()] = $hash;
        }

        return $this->templateHashes[$type->getName()];
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
