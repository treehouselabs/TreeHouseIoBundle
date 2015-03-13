<?php

namespace TreeHouse\IoIntegrationBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use TreeHouse\IoBundle\Entity\Feed;
use TreeHouse\IoBundle\Import\Feed\TransportFactory;
use TreeHouse\IoIntegrationBundle\Entity\Origin;

class LoadOriginData implements FixtureInterface
{
    /**
     * @inheritdoc
     */
    public function load(ObjectManager $manager)
    {
        $this->loadOrigin1($manager);

        $manager->flush();
    }

    private function loadOrigin1(ObjectManager $manager)
    {
        $origin = (new Origin())
            ->setId(1)
            ->setName('origin1')
            ->setTitle('Origin 1')
            ->setPriority(1)
        ;

        $feed = (new Feed())
            ->setOrigin($origin)
            ->setFrequency(1)
            ->setPartial(false)
            ->setType('itunes_podcast')
            ->setOptions([])
            ->setReaderType('xml')
            ->setReaderOptions([])
            ->setImporterType('default')
            ->setImporterOptions([])
            ->setDefaultValues([])
            ->setTransportConfig(TransportFactory::createConfigFromFile($this->getFeedFile('podcast.xml')))
        ;

        $manager->persist($origin);
        $manager->persist($feed);
    }

    /**
     * @param string $file
     *
     * @return string
     */
    private function getFeedFile($file)
    {
        return sprintf('%s/../../Resources/feeds/%s', __DIR__, $file);
    }
}
