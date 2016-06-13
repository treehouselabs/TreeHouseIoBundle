<?php

namespace TreeHouse\IoBundle\Import\Importer;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use TreeHouse\Feeder\Event\InvalidItemEvent;
use TreeHouse\Feeder\Event\ItemNotModifiedEvent;
use TreeHouse\Feeder\Feed;
use TreeHouse\Feeder\FeedEvents;
use TreeHouse\IoBundle\Entity\Import;
use TreeHouse\IoBundle\Import\Event\ExceptionEvent;
use TreeHouse\IoBundle\Import\Event\FailedItemEvent;
use TreeHouse\IoBundle\Import\Event\HandledItemEvent;
use TreeHouse\IoBundle\Import\Event\ItemEvent;
use TreeHouse\IoBundle\Import\Event\SkippedItemEvent;
use TreeHouse\IoBundle\Import\Event\SuccessItemEvent;
use TreeHouse\IoBundle\Import\Exception\FailedItemException;
use TreeHouse\IoBundle\Import\Feed\FeedItemBag;
use TreeHouse\IoBundle\Import\Handler\HandlerInterface;
use TreeHouse\IoBundle\Import\ImportEvents;
use TreeHouse\IoBundle\Import\ImportResult;
use TreeHouse\IoBundle\Model\SourceInterface;

class Importer implements EventSubscriberInterface
{
    /**
     * @var Import
     */
    protected $import;

    /**
     * @var HandlerInterface
     */
    protected $handler;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var ImportResult
     */
    protected $result;

    /**
     * @var int
     */
    protected $batchSize = 20;

    /**
     * @param Import                   $import
     * @param HandlerInterface         $handler
     * @param EventDispatcherInterface $dispatcher
     * @param int                      $batchSize
     */
    public function __construct(Import $import, HandlerInterface $handler, EventDispatcherInterface $dispatcher, $batchSize = 20)
    {
        $this->import = $import;
        $this->handler = $handler;
        $this->eventDispatcher = $dispatcher;
        $this->result = new ImportResult();

        $this->setBatchSize($batchSize);
        $this->eventDispatcher->addSubscriber($this);
    }

    /**
     * @return Import
     */
    public function getImport()
    {
        return $this->import;
    }

    /**
     * @return ImportResult
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents()
    {
        return [
            FeedEvents::ITEM_FILTERED => [['onItemFiltered']],
            FeedEvents::ITEM_INVALID => [['onItemInvalid']],
            FeedEvents::ITEM_FAILED => [['onItemFailed']],
        ];
    }

    /**
     * @return EventDispatcherInterface
     */
    public function getEventDispatcher()
    {
        return $this->eventDispatcher;
    }

    /**
     * @param int $size
     *
     * @throws \InvalidArgumentException When size is not a positive number
     */
    public function setBatchSize($size)
    {
        $batchSize = intval($size);

        if ($batchSize < 1) {
            throw new \InvalidArgumentException('Batch size needs to be higher than 0');
        }

        $this->batchSize = $batchSize;
    }

    /**
     * Dispatched when item is filtered from the feed.
     *
     * @param ItemNotModifiedEvent $event
     */
    public function onItemFiltered(ItemNotModifiedEvent $event)
    {
        $this->skipItem($event->getItem(), $event->getReason());
    }

    /**
     * Dispatched when item failed during modification.
     *
     * @param ItemNotModifiedEvent $event
     */
    public function onItemFailed(ItemNotModifiedEvent $event)
    {
        $this->failItem($event->getItem(), $event->getReason());
    }

    /**
     * Dispatched when item is processed but found invalid.
     *
     * @param InvalidItemEvent $event
     */
    public function onItemInvalid(InvalidItemEvent $event)
    {
        $this->failItem($event->getItem(), $event->getReason());
    }

    /**
     * @param string $name
     * @param Event  $event
     */
    public function dispatchEvent($name, Event $event)
    {
        $this->eventDispatcher->dispatch($name, $event);
    }

    /**
     * Runs the import.
     *
     * @param Feed $feed
     */
    public function run(Feed $feed)
    {
        while ($item = $this->getNextItem($feed)) {
            // dispatch event for next item
            $event = new ItemEvent($this, $item);
            $this->eventDispatcher->dispatch(ImportEvents::ITEM_START, $event);
            // import the item
            try {
                $source = $this->handleItem($item);

                $this->successItem($item, $source);
            } catch (FailedItemException $e) {
                $this->failItem($item, $e->getMessage());
            }

            // item done
            $this->eventDispatcher->dispatch(ImportEvents::ITEM_FINISH, $event);

            // clear entitymanager after batch
            if (($this->result->getProcessed() % $this->batchSize) === 0) {
                $this->flush();
                $this->clear();
            }
        }

        // flush remaining changes
        $this->flush();
        $this->clear();
    }

    /**
     * Returns the next item in the feed, or null if no more items are left.
     * Use this when iterating over the feed.
     *
     * @param Feed $feed
     *
     * @return FeedItemBag|null
     */
    protected function getNextItem(Feed $feed)
    {
        try {
            return $feed->getNextItem();
        } catch (\Exception $exception) {
            $this->handleException($exception);
        }

        return null;
    }

    /**
     * @param FeedItemBag $item
     *
     * @return SourceInterface
     */
    protected function handleItem(FeedItemBag $item)
    {
        $source = $this->handler->handle($item);

        $this->eventDispatcher->dispatch(
            ImportEvents::ITEM_HANDLED,
            new HandledItemEvent(
                $this,
                $item,
                $source
            )
        );

        return $source;
    }

    /**
     * Dispatches an event indicating a successfully handled item.
     *
     * @param FeedItemBag     $item
     * @param SourceInterface $result
     */
    protected function successItem(FeedItemBag $item, SourceInterface $result)
    {
        $this->result->incrementSuccess();

        $event = new SuccessItemEvent($this, $item, $result);
        $this->eventDispatcher->dispatch(ImportEvents::ITEM_SUCCESS, $event);
    }

    /**
     * Dispatches an event indicating a skipped item.
     *
     * @param FeedItemBag $item
     * @param string      $reason
     */
    protected function skipItem(FeedItemBag $item, $reason = '')
    {
        $this->result->incrementSkipped();

        $event = new SkippedItemEvent($this, $item, $reason);
        $this->eventDispatcher->dispatch(ImportEvents::ITEM_SKIPPED, $event);
    }

    /**
     * Dispatches an event indicating a failed item.
     *
     * @param FeedItemBag $item
     * @param string      $reason
     */
    protected function failItem(FeedItemBag $item, $reason)
    {
        $this->result->incrementFailed();

        $event = new FailedItemEvent($this, $item, $reason);
        $this->eventDispatcher->dispatch(ImportEvents::ITEM_FAILED, $event);
    }

    /**
     * Dispatches exception event.
     *
     * @param \Exception $exception
     */
    protected function handleException(\Exception $exception)
    {
        $this->eventDispatcher->dispatch(ImportEvents::EXCEPTION, new ExceptionEvent($this, $exception));
    }

    /**
     * Flushes outstanding changes.
     */
    protected function flush()
    {
        $this->handler->flush();
    }

    protected function clear()
    {
        $this->handler->clear();
    }
}
