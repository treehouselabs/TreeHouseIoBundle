<?php

namespace TreeHouse\IoBundle\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use TreeHouse\IoBundle\Event\SourceEvent;
use TreeHouse\IoBundle\IoEvents;
use TreeHouse\IoBundle\Model\SourceInterface;
use TreeHouse\IoBundle\Source\Processor\DelegatingSourceProcessor;
use TreeHouse\IoBundle\Source\SourceProcessorInterface;

/**
 * Handles job scheduling for source events.
 *
 * A source is updated every time it's checked during an import.
 * Regardless if anything actually changed, the datetime_last_visited
 * value is updated. To prevent events from triggering on each of
 * these updates, we check if there is anything else but the this
 * date in the changeset.
 */
class SourceModificationListener
{
    /**
     * @var DelegatingSourceProcessor
     */
    protected $sourceProcessor;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @var SourceInterface[]
     */
    protected $sources = [];

    /**
     * @param SourceProcessorInterface $sourceProcessor
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(SourceProcessorInterface $sourceProcessor, EventDispatcherInterface $dispatcher)
    {
        $this->sourceProcessor = $sourceProcessor;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param OnFlushEventArgs $args
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        $uow = $args->getEntityManager()->getUnitOfWork();
        $entities = array_merge(
            $uow->getScheduledEntityInsertions(),
            $uow->getScheduledEntityUpdates()
        );

        // remember sources that are to be updated
        foreach ($entities as $entity) {
            if (!$entity instanceof SourceInterface) {
                continue;
            }

            $modified = $this->isSourceModified($entity, $uow);

            // update modification date when it's modified
            if ($modified) {
                $this->setSourceModificationDate($entity, $uow);
            }

            if ($modified || !$this->sourceProcessor->isLinked($entity)) {
                $this->sources[] = $entity;
            }
        }
    }

    /**
     * @param PostFlushEventArgs $args
     */
    public function postFlush(PostFlushEventArgs $args)
    {
        foreach ($this->sources as $source) {
            $this->dispatcher->dispatch(IoEvents::SOURCE_PROCESS, new SourceEvent($source));
        }

        $this->sources = [];
    }

    /**
     * Checks whether the changeset for the given source includes anything else besides the last-visited date.
     *
     * @param SourceInterface $source
     * @param UnitOfWork      $uow
     *
     * @return bool
     */
    protected function isSourceModified(SourceInterface $source, UnitOfWork $uow)
    {
        $changeset = $uow->getEntityChangeSet($source);

        $changeset = $this->filterChangeset($changeset);

        // see if any other keys besides datetimeLastVisited and messages is in changeset
        return !empty($changeset);
    }

    /**
     * @param array $changeset
     *
     * @return array
     */
    public function filterChangeset($changeset)
    {
        foreach ($changeset as $field => $values) {
            if (in_array($field, ['datetimeLastVisited', 'messages'])) {
                unset($changeset[$field]);
            } elseif (is_array($values[0]) && is_array($values[1])) {
                $changeset[$field] = $this->recursiveDiff($values[0], $values[1]);
                if (empty($changeset[$field])) {
                    unset($changeset[$field]);
                }
            } elseif ($values[0] == $values[1]) {
                unset($changeset[$field]);
            }
        };

        return $changeset;
    }

    /**
     * @param array $valuesA
     * @param array $valuesB
     *
     * @return array
     */
    public function recursiveDiff($valuesA, $valuesB)
    {
        $diff = [];

        foreach ($valuesA as $key => $value) {
            if (is_array($valuesB) && array_key_exists($key, $valuesB)) {
                if (is_array($value)) {
                    $subDiff = $this->recursiveDiff($value, $valuesB[$key]);
                    if (count($subDiff)) {
                        $diff[$key] = $subDiff;
                    }
                } elseif ($value != $valuesB[$key]) {
                    $diff[$key] = $value;
                }
            } else {
                $diff[$key] = $value;
            }
        }

        return $diff;
    }

    /**
     * Updates modification date for a source.
     *
     * @param SourceInterface $source
     * @param UnitOfWork      $uow
     */
    protected function setSourceModificationDate(SourceInterface $source, UnitOfWork $uow)
    {
        $uow->scheduleExtraUpdate(
            $source,
            ['datetimeModified' => [
                0 => $source->getDatetimeModified(),
                1 => new \DateTime(),
            ]]
        );
    }
}
