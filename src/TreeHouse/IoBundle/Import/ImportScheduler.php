<?php

namespace TreeHouse\IoBundle\Import;

use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use TreeHouse\IoBundle\Entity\Feed;
use TreeHouse\IoBundle\Entity\FeedRepository;
use TreeHouse\IoBundle\Entity\ImportPart;
use TreeHouse\IoBundle\Entity\ImportPartRepository;
use TreeHouse\IoBundle\Entity\ImportRepository;
use TreeHouse\IoBundle\Import\Event\PartEvent;

class ImportScheduler
{
    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    /**
     * @var ImportFactory
     */
    protected $importFactory;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @param ManagerRegistry          $doctrine
     * @param ImportFactory            $importFactory
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(ManagerRegistry $doctrine, ImportFactory $importFactory, EventDispatcherInterface $dispatcher)
    {
        $this->doctrine        = $doctrine;
        $this->importFactory   = $importFactory;
        $this->eventDispatcher = $dispatcher;
    }

    /**
     * @param integer $id
     *
     * @return Feed
     */
    public function findFeed($id)
    {
        return $this->getFeedRepository()->find($id);
    }

    /**
     * @return array<integer, integer>
     */
    public function findAll()
    {
        $result = [];

        /** @var Feed $feed */
        foreach ($this->getFeedRepository()->findAll() as $feed) {
            $result[$feed->getId()] = 100;
        }

        return $result;
    }

    /**
     * @param array $ids
     *
     * @return array<integer, integer>
     */
    public function findByIds(array $ids)
    {
        $result = [];

        foreach ($ids as $id) {
            if (null !== $feed = $this->getFeedRepository()->find($id)) {
                $result[$feed->getId()] = 100;
            }
        }

        return $result;
    }

    /**
     * @param integer $minutes
     *
     * @return array<integer, integer>
     */
    public function findByTime($minutes)
    {
        $date = new \DateTime(sprintf('+%d minutes', $minutes));

        // this many minutes for a complete cycle
        $cycleMinutes = $this->getFeedRepository()->findHighestFrequencyValue() * 60;

        // get priority for all feeds, and add to the total number of imports
        $feeds = [];
        $importsInCycle = 0;

        /** @var Feed $feed */
        foreach ($this->getFeedRepository()->findAll() as $feed) {
            if (0 === $feed->getFrequency()) {
                continue;
            }

            $feeds[$feed->getId()] = $this->getFeedPriority($feed, $date);
            $importsInCycle += floor($cycleMinutes / ($feed->getFrequency() * 60));
        }

        // filter out high priority feeds
        $highPriority = array_filter($feeds, function ($priority) {
            return $priority >= 100;
        });

        // if there are any high priority feeds, schedule them first, right now
        if (!empty($highPriority)) {
            return $highPriority;
        }

        // sort by priority
        arsort($feeds, SORT_NUMERIC);

        // if we have time between imports, see if we have to wait
        // TODO we could determine this based on average import times in the past, for now use 1 minute per import
        $totalTime = $importsInCycle;

        // see if delta has passed since last import
        $lastImport = $this->getImportRepository()->findOneLatestStarted();
        $diff = (time() - $lastImport->getDatetimeStarted()->getTimestamp()) / 60;
        $delta = ($cycleMinutes - $totalTime) / $importsInCycle;
        if ($diff < $delta) {
            // delta not yet passed
            return;
        }

        // how many should we import?
        $numberOfFeeds = round($minutes / ($cycleMinutes / $totalTime));

        // safeguard against too low value
        if ($numberOfFeeds < 1) {
            $numberOfFeeds = 1;
        }

        // return the number of feeds we can schedule
        return array_slice($feeds, 0, $numberOfFeeds, true);
    }

    /**
     * @return ImportPart[]
     */
    public function findUnfinishedParts()
    {
        return $this->getImportPartRepository()->findStartedButUnfinishedParts();
    }

    /**
     * @return ImportPart[]
     */
    public function findOverdueParts()
    {
        return $this->getImportPartRepository()->findOverdueParts();
    }

    /**
     * @param ImportPart $part
     */
    public function schedulePart(ImportPart $part)
    {
        $part->setDatetimeScheduled(new \DateTime());
        $this->getImportRepository()->savePart($part);

        $this->eventDispatcher->dispatch(ImportEvents::PART_SCHEDULED, new PartEvent($part));
    }

    /**
     * Calculates the priority for this feed to be imported. It looks at the
     * feed's frequency and last import start date for this.
     *
     * Example:
     *
     * Feed 1 is imported every hour, feed 2 every 6 hours. We allow feeds to
     * be imported 10% before their scheduled time. So feed 1 may be imported
     * 54 minutes after the last import started, and feed 2 after 5 hours.
     *
     * The priority increases for both feeds as the scheduled time approaches,
     * but it increases more for feed 2: since it is imported every 6 hours, 5
     * minutes before passing 6 hours is more urgent than 5 minutes before 1
     * hour passes.
     *
     * The reverse is true when the scheduled time has passed, and the import is
     * overdue: feed 2 has a little less than 6 hours to run twice, whereas feed
     * 1 has less than an hour for this.
     *
     * Finally: feeds that have never been imported have the highest priority;
     *
     * @param Feed      $feed
     * @param \DateTime $date
     *
     * @throws \InvalidArgumentException When given date is in the past
     *
     * @return double
     */
    protected function getFeedPriority(Feed $feed, \DateTime $date)
    {
        if ($date < new \DateTime()) {
            throw new \InvalidArgumentException();
        }

        // see if there are scheduled imports, if so: this has no priority
        if (!empty($this->getImportRepository()->findScheduledByFeed($feed))) {
            return -INF;
        }

        // get the last started import
        if (null === $import = $this->getImportRepository()->findOneLatestStartedByFeed($feed)) {
            // import is not scheduled and hasn't run yet, this has the highest priority
            return INF;
        }

        $last  = $import->getDatetimeStarted();
        $since = $date->getTimestamp() - $last->getTimestamp();

        $frequency = $feed->getFrequency() * 3600; // frequency in seconds
        $delta     = $frequency * 0.1;             // 10% of the frequency
        $eligible  = $frequency - $delta;          // 90% of the frequency

        return ($since - $eligible) / $delta * 100;
    }

    /**
     * @return FeedRepository
     */
    protected function getFeedRepository()
    {
        return $this->doctrine->getRepository('TreeHouseIoBundle:Feed');
    }

    /**
     * @return ImportRepository
     */
    protected function getImportRepository()
    {
        return $this->doctrine->getRepository('TreeHouseIoBundle:Import');
    }

    /**
     * @return ImportPartRepository
     */
    protected function getImportPartRepository()
    {
        return $this->doctrine->getRepository('TreeHouseIoBundle:ImportPart');
    }
}
