<?php

namespace TreeHouse\IoBundle\Entity;

use Doctrine\ORM\EntityRepository;

class ImportPartRepository extends EntityRepository
{
    /**
     * @return ImportPart[]
     */
    public function findStartedButUnfinishedParts()
    {
        $builder = $this->createQueryBuilder('p')
            ->where('p.datetimeStarted IS NOT NULL')
            ->andWhere('p.datetimeEnded IS NULL')
            ->orderBy('p.datetimeStarted', 'ASC')
        ;

        return $builder->getQuery()->getResult();
    }

    /**
     * @param string $time
     *
     * @return ImportPart[]
     */
    public function findOverdueParts($time = '30 minutes')
    {
        $date = new \Datetime('-'.$time);

        $builder = $this->createQueryBuilder('p')
            ->where('p.datetimeStarted IS NULL')
            ->andWhere('p.datetimeScheduled < :overdueDate')
            ->setParameter('overdueDate', $date)
            ->orderBy('p.datetimeScheduled', 'ASC')
        ;

        return $builder->getQuery()->getResult();
    }

    /**
     * @param integer $importId
     *
     * @return ImportPart[]
     */
    public function findUnstartedByImport($importId)
    {
        $builder = $this->createQueryBuilder('p')
            ->where('p.datetimeStarted IS NULL')
            ->andWhere('p.import = :import')
            ->setParameter('import', $importId)
            ->orderBy('p.datetimeScheduled', 'ASC')
        ;

        return $builder->getQuery()->getResult();
    }
}
