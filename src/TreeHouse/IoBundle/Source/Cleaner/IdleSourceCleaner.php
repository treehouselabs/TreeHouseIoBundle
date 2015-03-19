<?php

namespace TreeHouse\IoBundle\Source\Cleaner;

use Doctrine\Common\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use TreeHouse\IoBundle\Entity\Feed;
use TreeHouse\IoBundle\Entity\ImportRepository;
use TreeHouse\IoBundle\Event\FeedCleanupEvent;
use TreeHouse\IoBundle\Event\FeedCleanupHaltEvent;
use TreeHouse\IoBundle\Event\FeedEvent;
use TreeHouse\IoBundle\IoEvents;
use TreeHouse\IoBundle\Source\SourceManagerInterface;

class IdleSourceCleaner implements SourceCleanerInterface
{
    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    /**
     * @var SourceManagerInterface
     */
    protected $sourceManager;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param ManagerRegistry          $doctrine
     * @param SourceManagerInterface   $sourceManager
     * @param EventDispatcherInterface $dispatcher
     * @param LoggerInterface          $logger
     */
    public function __construct(
        ManagerRegistry $doctrine,
        SourceManagerInterface $sourceManager,
        EventDispatcherInterface $dispatcher,
        LoggerInterface $logger
    ) {
        $this->doctrine        = $doctrine;
        $this->sourceManager   = $sourceManager;
        $this->eventDispatcher = $dispatcher;
        $this->logger          = $logger;
    }

    /**
     * @inheritdoc
     */
    public function clean(DelegatingSourceCleaner $cleaner, ThresholdVoterInterface $voter)
    {
        $numCleaned = 0;

        $query = $this->doctrine
            ->getRepository('TreeHouseIoBundle:Feed')
            ->createQueryBuilder('f')
            ->getQuery()
        ;

        /** @var Feed $feed */
        foreach ($query->iterate() as list($feed)) {
            if (false !== $cleaned = $this->cleanFeed($cleaner, $feed, $voter, $numCleaned)) {
                $numCleaned += $cleaned;
            }
        }

        return $numCleaned;
    }

    /**
     * @param DelegatingSourceCleaner $cleaner
     * @param Feed                    $feed
     * @param ThresholdVoterInterface $voter
     *
     * @return bool
     */
    public function cleanFeed(DelegatingSourceCleaner $cleaner, Feed $feed, ThresholdVoterInterface $voter)
    {
        if (null === $expireDate = $this->getLastFullImportDate($feed)) {
            $this->logger->debug(
                sprintf('Skipping %s, because it has no recent imports', $feed)
            );

            $this->eventDispatcher->dispatch(IoEvents::FEED_CLEANUP_SKIP, new FeedCleanupEvent($feed, 0));

            return false;
        }

        $this->eventDispatcher->dispatch(IoEvents::PRE_CLEAN_FEED, new FeedEvent($feed));

        $this->logger->debug(
            sprintf(
                'Checking sources of %s that have not been visited since %s',
                $feed,
                $expireDate->format('Y-m-d H:i:s')
            )
        );

        // get sources that haven't been visited since $expireDate
        $sourceRepo = $this->sourceManager->getRepository();
        $count = $sourceRepo->countByFeedAndUnvisitedSince($feed, $expireDate);

        // fail safe: see if percentage of sources to be removed is not too high
        $total = $sourceRepo->countByFeed($feed);
        $max   = $this->getThreshold($total);

        // see if threshold is reached
        if ($count > $max) {
            $message = sprintf(
                'Stopping cleanup for %s, because %s of %s sources were to be deleted, %s is the maximum.',
                $feed,
                $count,
                $total,
                $max
            );

            if (!$voter->vote($count, $total, $max, $message)) {
                $this->eventDispatcher->dispatch(
                    IoEvents::FEED_CLEANUP_HALT,
                    new FeedCleanupHaltEvent($feed, $count, $total, $max)
                );

                return false;
            }
        }

        $this->logger->debug(
            sprintf('Cleaning %d sources for %s', $count, $feed)
        );

        $builder = $sourceRepo->queryByFeedAndUnvisitedSince($feed, $expireDate);
        $numCleaned = $cleaner->cleanByQuery($builder->getQuery());

        $this->eventDispatcher->dispatch(IoEvents::POST_CLEAN_FEED, new FeedCleanupEvent($feed, $numCleaned));

        return $numCleaned;
    }

    /**
     * Returns the last date after which the given feed has had a full import.
     *
     * @param Feed $feed
     *
     * @return \DateTime
     */
    public function getLastFullImportDate(Feed $feed)
    {
        // we can only have a full import when the feed is not partial
        if ($feed->isPartial()) {
            return null;
        }

        $imports = $this->getImportRepository()->findCompletedByFeed($feed);

        // find the import dates for this feed, but only non-partial imports
        $dates = [];
        foreach ($imports as $import) {
            // don't count imports with errors
            if ($import->hasErrors()) {
                continue;
            }

            // don't count partial imports
            if ($import->isPartial()) {
                continue;
            }

            // imports without any items are excluded also
            if ($import->getTotalNumberOfItems() === 0) {
                continue;
            }

            $dates[] = $import->getDatetimeStarted();
        }

        // if we have no date for this feed, we can't consider it to be fully imported
        if (empty($dates)) {
            return null;
        }

        // return the latest of the dates
        return max($dates);
    }

    /**
     * Calculates maximum number of cleanups that may take place.
     *
     * @param integer $total
     * @param integer $factor
     *
     * @return double
     *
     * @see http://math.stackexchange.com/a/398263/78794
     */
    protected function getThreshold($total, $factor = 6)
    {
        $ratio = (3 * pow(100, 1/$factor) - 3) / ((17 * pow($total, 1/$factor)) + (3 * pow(100, 1/$factor)) - 20);

        return ceil($total * $ratio);
    }

    /**
     * @return ImportRepository
     */
    protected function getImportRepository()
    {
        return $this->doctrine->getRepository('TreeHouseIoBundle:Import');
    }
}
