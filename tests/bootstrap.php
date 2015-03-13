<?php

use Doctrine\Common\Annotations\AnnotationRegistry;

$file = __DIR__.'/../vendor/autoload.php';
if (!file_exists($file)) {
    throw new RuntimeException('Install dependencies to run test suite.');
}

$loader = require $file;
$loader->addPsr4('TreeHouse\\IoIntegrationBundle\\', __DIR__ . '/src/TreeHouse/IoIntegrationBundle');

AnnotationRegistry::registerLoader([$loader, 'loadClass']);
