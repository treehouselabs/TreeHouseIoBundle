<?php

namespace TreeHouse\IoBundle\Tests\Import;

use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use TreeHouse\IoBundle\Entity\Feed;
use TreeHouse\IoBundle\Entity\Import;
use TreeHouse\IoBundle\Import\ImportFactory;
use TreeHouse\IoIntegrationBundle\DataFixtures\ORM\LoadOriginData;

class ImportJobTest extends WebTestCase
{
    protected function setUp()
    {
        static::bootKernel([]);

        $doctrine = static::$kernel->getContainer()->get('doctrine')->getManager();

        $meta = $doctrine->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($doctrine);
        $schemaTool->dropSchema($meta);
        $schemaTool->createSchema($meta);

        $fixture = new LoadOriginData();
        $fixture->load($doctrine);
    }

    public function testImportJob()
    {
        $import = $this->runImport();

        $this->assertEquals(98, $import->getSuccess());
        $this->assertEquals(1, $import->getFailed());
        $this->assertEquals(1, $import->getSkipped());
    }

    public function testImportJobWithForce()
    {
        // run import
        $import1 = $this->runImport();
        $this->assertEquals(98, $import1->getSuccess());
        $this->assertEquals(1, $import1->getFailed());
        $this->assertEquals(1, $import1->getSkipped());

        // the second time everything should skip
        $import2 = $this->runImport();
        $this->assertEquals(0, $import2->getSuccess());
        $this->assertEquals(1, $import2->getFailed());
        $this->assertEquals(99, $import2->getSkipped());

        // now import with force
        $import3 = $this->runImport(true);
        $this->assertEquals(98, $import3->getSuccess());
        $this->assertEquals(1, $import3->getFailed());
        $this->assertEquals(1, $import3->getSkipped());
    }

    /**
     * @param bool $force
     *
     * @return Import
     */
    protected function runImport($force = false)
    {
        $importFactory = $this->getImportFactory();

        $import = $importFactory->createImport($this->getFeed(), new \DateTime(), $force);

        foreach ($import->getParts() as $part) {
            $job = $importFactory->createImportJob($part);
            $job->run();
        }

        return $import;
    }

    /**
     * @return Feed
     */
    protected function getFeed()
    {
        $doctrine = static::$kernel->getContainer()->get('doctrine');

        return $doctrine->getRepository('TreeHouseIoBundle:Feed')->findOneBy([]);
    }

    /**
     * @return ImportFactory
     */
    protected function getImportFactory()
    {
        return static::$kernel->getContainer()->get('tree_house.io.import.import_factory');
    }
}
