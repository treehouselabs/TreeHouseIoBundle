<?php

namespace TreeHouse\IoIntegrationBundle\Source;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use TreeHouse\IoBundle\Model\SourceInterface;
use TreeHouse\IoBundle\Source\SourceProcessorInterface;
use TreeHouse\IoIntegrationBundle\Entity\Episode;
use TreeHouse\IoIntegrationBundle\Entity\Source;

class SourceProcessor implements SourceProcessorInterface
{
    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    /**
     * @param ManagerRegistry $doctrine
     */
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * @inheritdoc
     *
     * @param Source $source
     */
    public function link(SourceInterface $source)
    {
        $manager = $this->doctrine->getManager();
        $repo = $this->doctrine->getRepository('TreeHouseIoIntegrationBundle:Episode');

        $number = $source->getData()['number'];
        if (null === $repo->findOneBy(['number' => $number])) {
            $episode = new Episode();
            $this->setData($episode, $source->getData());

            $episode->addSource($source);
            $source->setEpisode($episode);

            $manager->persist($episode);
            $manager->flush($episode);
        }
    }

    /**
     * @inheritdoc
     *
     * @param Source $source
     */
    public function unlink(SourceInterface $source)
    {
        $source->setEpisode(null);
    }

    /**
     * @inheritdoc
     *
     * @param Source $source
     */
    public function isLinked(SourceInterface $source)
    {
        return $source->getEpisode() !== null;
    }

    /**
     * @inheritdoc
     *
     * @param Source $source
     */
    public function process(SourceInterface $source)
    {
        $this->setData($source->getEpisode(), $source->getData());

        $this->doctrine->getManager()->flush($source->getEpisode());
    }

    /**
     * @inheritdoc
     *
     * @param Source $source
     */
    public function supports(SourceInterface $source)
    {
        return $source instanceof Source;
    }

    /**
     * @param Episode $episode
     * @param array   $data
     */
    protected function setData(Episode $episode, array $data)
    {
        /** @var EntityManagerInterface $manager */
        $manager = $this->doctrine->getManager();

        $episode->setNumber($data['number']);
        $episode->setTitle($data['title']);
        $episode->setBody($data['body']);
        $episode->setDatetimePublished(new \DateTime($data['datetime_published']));
        $episode->setAudioUrl($data['audio_url']);
        $episode->setImageUrl($data['image_url']);
        $episode->setAuthor($manager->getReference('TreeHouseIoIntegrationBundle:Author', $data['author']['id']));
        $episode->setDuration($data['duration']);
        $episode->setSummary($data['summary']);
    }
}
