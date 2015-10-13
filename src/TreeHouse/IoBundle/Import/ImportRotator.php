<?php

namespace TreeHouse\IoBundle\Import;

use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use TreeHouse\IoBundle\Entity\Feed;
use TreeHouse\IoBundle\Entity\ImportRepository;
use TreeHouse\IoBundle\Import\Event\ImportEvent;

class ImportRotator
{
    /**
     * @var RegistryInterface
     */
    protected $doctrine;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @param RegistryInterface        $doctrine
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(RegistryInterface $doctrine, EventDispatcherInterface $dispatcher)
    {
        $this->doctrine = $doctrine;
        $this->eventDispatcher = $dispatcher;
    }

    /**
     * @return EventDispatcherInterface
     */
    public function getEventDispatcher()
    {
        return $this->eventDispatcher;
    }

    /**
     * Rotates imported feeds.
     *
     * @param Feed $feed The feed to rotate imports for
     * @param int  $max  The number of imports to keep
     */
    public function rotate(Feed $feed, $max = 4)
    {
        /** @var ImportRepository $repo */
        $repo = $this->doctrine->getRepository('TreeHouseIoBundle:Import');
        $imports = $repo->findCompletedByFeed($feed);

        if (sizeof($imports) <= $max) {
            return;
        }

        $manager = $this->doctrine->getManager();
        foreach (array_slice($imports, $max) as $import) {
            $this->eventDispatcher->dispatch(ImportEvents::IMPORT_ROTATE, new ImportEvent($import));
            $manager->remove($import);
            $manager->flush();
        }
    }
}
