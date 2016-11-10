<?php

namespace TreeHouse\IoBundle\Entity;

use Doctrine\ORM\EntityRepository;
use TreeHouse\IoBundle\Exception\UnfinishedImportException;
use TreeHouse\IoBundle\Import\ImportResult;

class ImportRepository extends EntityRepository
{
    /**
     * Find scheduled imports by feed.
     *
     * @param Feed $feed
     *
     * @return Import[]
     */
    public function findScheduledByFeed(Feed $feed)
    {
        $builder = $this->createQueryBuilder('i')
            ->where('i.feed = :feed')
            ->andWhere('i.datetimeStarted IS NULL')
            ->setParameter('feed', $feed)
        ;

        return $builder->getQuery()->getResult();
    }

    /**
     * Find latest started import.
     *
     * @return Import
     */
    public function findOneLatestStarted()
    {
        $builder = $this->createQueryBuilder('i')
            ->andWhere('i.datetimeStarted IS NOT NULL')
            ->andWhere('SIZE(i.parts) > 0')
            ->orderBy('i.datetimeStarted', 'DESC')
            ->setMaxResults(1)
        ;

        return $builder->getQuery()->getOneOrNullResult();
    }

    /**
     * Find latest started import by feed.
     *
     * @param Feed $feed
     *
     * @return Import
     */
    public function findOneLatestStartedByFeed(Feed $feed)
    {
        $builder = $this->createQueryBuilder('i')
            ->where('i.feed = :feed')
            ->andWhere('i.datetimeStarted IS NOT NULL')
            ->andWhere('SIZE(i.parts) > 0')
            ->orderBy('i.datetimeStarted', 'DESC')
            ->setMaxResults(1)
            ->setParameter('feed', $feed)
        ;

        return $builder->getQuery()->getOneOrNullResult();
    }

    /**
     * Find imports by feed, ordered by descending start date.
     *
     * @param Feed $feed
     *
     * @return Import[]
     */
    public function findCompletedByFeed(Feed $feed)
    {
        $builder = $this->createQueryBuilder('i')
            ->where('i.feed = :feed')
            ->andWhere('i.datetimeStarted IS NOT NULL')
            ->andWhere('i.datetimeEnded IS NOT NULL')
            ->orderBy('i.datetimeStarted', 'DESC')
            ->setParameter('feed', $feed)
        ;

        return $builder->getQuery()->getResult();
    }

    /**
     * Find imports by number of parts.
     *
     * @param int    $number   The number of parts
     * @param string $operator The operator to use. Possible values are `=`, `<`, `>`, `<=`, and `>=`
     *
     * @return Import[]
     */
    public function findByNumberOfParts($number, $operator = '=')
    {
        $builder = $this->createQueryBuilder('i')
            ->andWhere(sprintf('SIZE(i.parts) %s :parts', $operator))
            ->setParameter('parts', $number)
        ;

        return $builder->getQuery()->getResult();
    }

    /**
     * Find imports that have started but not yet finished.
     *
     * @return Import[]
     */
    public function findStartedButUnfinished()
    {
        $builder = $this->createQueryBuilder('i')
            ->andWhere('i.datetimeStarted IS NOT NULL')
            ->andWhere('i.datetimeEnded IS NULL')
            ->orderBy('i.datetimeStarted', 'DESC')
        ;

        return $builder->getQuery()->getResult();
    }

    /**
     * @param Import $import
     * @param bool   $autoFlush
     */
    public function save(Import $import, $autoFlush = true)
    {
        $this->getEntityManager()->persist($import);

        if ($autoFlush) {
            $this->getEntityManager()->flush($import);
        }
    }

    /**
     * @param ImportPart $part
     * @param bool       $autoFlush
     */
    public function savePart(ImportPart $part, $autoFlush = true)
    {
        $this->getEntityManager()->persist($part);

        if ($autoFlush) {
            $this->getEntityManager()->flush($part);
        }
    }

    /**
     * @param Import    $import
     * @param array     $transport
     * @param \DateTime $scheduleDate
     * @param int       $position
     *
     * @return ImportPart
     */
    public function createPart(Import $import, array $transport, \DateTime $scheduleDate = null, $position = null)
    {
        if (is_null($scheduleDate)) {
            $scheduleDate = new \DateTime();
        }

        if (is_null($position)) {
            $position = 0;

            /** @var ImportPart $part */
            foreach ($import->getParts() as $part) {
                if ($part->getPosition() > $position) {
                    $position = $part->getPosition();
                }
            }

            ++$position;
        }

        $part = new ImportPart();
        $part->setPosition($position);
        $part->setTransportConfig($transport);
        $part->setDatetimeScheduled($scheduleDate);
        $part->setImport($import);
        $import->addPart($part);

        $this->getEntityManager()->persist($part);
        $this->getEntityManager()->flush($part);

        return $part;
    }

    /**
     * @param Import $import
     */
    public function startImport(Import $import)
    {
        $import->setDatetimeStarted(new \DateTime());
        $this->save($import);
    }

    /**
     * @param Import $import
     *
     * @throws \RuntimeException
     * @throws UnfinishedImportException
     *
     * @return bool
     */
    public function finishImport(Import $import)
    {
        if (!$import->isStarted()) {
            throw new \RuntimeException('Import has not yet started');
        }

        if ($this->importHasUnfinishedParts($import)) {
            throw UnfinishedImportException::create($import);
        }

        // set number of errored parts
        $erroredParts = $import
            ->getParts()
            ->filter(function (ImportPart $part) {
                return $part->getError();
            })
            ->count()
        ;

        $import->setErroredParts($erroredParts);

        // set end-date/time
        $import->setDatetimeEnded(new \DateTime());

        // flush the end result, and finish the log
        $this->save($import);
    }

    /**
     * @param ImportPart $part
     */
    public function startImportPart(ImportPart $part)
    {
        $part->setError(null);
        $part->setDatetimeStarted(new \DateTime());
        $this->savePart($part);
    }

    /**
     * @param ImportPart $part
     */
    public function finishImportPart(ImportPart $part)
    {
        if (!$part->isStarted()) {
            throw new \RuntimeException('Import part has not yet started');
        }

        $part->setDatetimeEnded(new \DateTime());
        $this->savePart($part);
    }

    /**
     * Checks if the import has any parts that are unfinished.
     *
     * @param Import $import  The import
     * @param bool   $refresh Whether to refresh the checked parts first. This
     *                        is useful when time has passed since the import
     *                        start, and you want to avoid race conditions
     *
     * @return bool
     */
    public function importHasUnfinishedParts(Import $import, $refresh = true)
    {
        foreach ($import->getParts() as $part) {
            if ($refresh === true) {
                $this->getEntityManager()->refresh($part);
            }

            if (!$part->isFinished()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Import       $import
     * @param ImportResult $result
     */
    public function addResult(Import $import, ImportResult $result)
    {
        $query = $this
            ->createQueryBuilder('i')
            ->update()
            ->set('i.success', 'i.success + :success')
            ->set('i.failed', 'i.failed + :failed')
            ->set('i.skipped', 'i.skipped + :skipped')
            ->where('i.id = :id')
            ->getQuery()
        ;

        $query->execute([
            'id' => $import->getId(),
            'success' => $result->getSuccess(),
            'failed' => $result->getFailed(),
            'skipped' => $result->getSkipped(),
        ]);

        $this->getEntityManager()->refresh($import);
    }
}
