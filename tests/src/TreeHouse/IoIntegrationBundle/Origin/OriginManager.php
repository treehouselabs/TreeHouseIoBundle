<?php

namespace TreeHouse\IoIntegrationBundle\Origin;

use Doctrine\Common\Persistence\ManagerRegistry;
use TreeHouse\IoBundle\Model\OriginInterface;
use TreeHouse\IoBundle\Origin\OriginManagerInterface;

class OriginManager implements OriginManagerInterface
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
     */
    public function getRepository()
    {
        return $this->doctrine->getRepository('TreeHouseIoIntegrationBundle:Origin');
    }

    /**
     * @inheritdoc
     */
    public function findById($originId)
    {
        return $this->getRepository()->find($originId);
    }

    /**
     * @inheritdoc
     */
    public function persist(OriginInterface $origin)
    {
        $this->doctrine->getManager()->persist($origin);
    }

    /**
     * @inheritdoc
     */
    public function remove(OriginInterface $origin)
    {
        $this->doctrine->getManager()->remove($origin);
    }

    /**
     * @inheritdoc
     */
    public function detach(OriginInterface $origin)
    {
        $this->doctrine->getManager()->detach($origin);
    }

    /**
     * @inheritdoc
     */
    public function flush()
    {
        $this->doctrine->getManager()->flush('TreeHouseIoIntegrationBundle:Origin');
    }

    /**
     * @inheritdoc
     */
    public function clear()
    {
        $this->doctrine->getManager()->clear('TreeHouseIoIntegrationBundle:Origin');
    }
}
