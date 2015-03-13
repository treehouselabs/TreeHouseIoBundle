<?php

use Doctrine\Common\Annotations\AnnotationRegistry;
use Composer\Autoload\ClassLoader;

/**
 * @var ClassLoader $loader
 */
$loader = require __DIR__ . '/../../vendor/autoload.php';
$loader->addPsr4('TreeHouse\\IoIntegrationBundle\\', __DIR__ . '/../src/TreeHouse/IoIntegrationBundle/');

AnnotationRegistry::registerLoader([$loader, 'loadClass']);

return $loader;
