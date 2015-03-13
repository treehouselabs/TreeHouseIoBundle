<?php

namespace TreeHouse\IoBundle\Bridge\WorkerBundle\Executor;

use FM\WorkerBundle\Monolog\LoggerAggregate;
use FM\WorkerBundle\Queue\JobExecutor;
use FM\WorkerBundle\Queue\ObjectPayloadInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use TreeHouse\IoBundle\Exception\SourceLinkException;
use TreeHouse\IoBundle\Exception\SourceProcessException;
use TreeHouse\IoBundle\Model\SourceInterface;
use TreeHouse\IoBundle\Source\SourceManagerInterface;
use TreeHouse\IoBundle\Source\SourceProcessorInterface;

/**
 * Worker job to link this source to existing entities, or create a
 * new entity when an entity with the same features doesn't already
 * exist
 *
 * Source process jobs are added to the queue by the SourceModificationListener
 */
class SourceProcessExecutor extends JobExecutor implements LoggerAggregate, ObjectPayloadInterface
{
    const NAME = 'source.process';

    /**
     * @var SourceManagerInterface
     */
    protected $sourceManager;

    /**
     * @var SourceProcessorInterface
     */
    protected $processor;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param SourceManagerInterface   $sourceManager
     * @param SourceProcessorInterface $processor
     * @param LoggerInterface          $logger
     */
    public function __construct(SourceManagerInterface $sourceManager, SourceProcessorInterface $processor, LoggerInterface $logger)
    {
        $this->sourceManager = $sourceManager;
        $this->processor     = $processor;
        $this->logger        = $logger;
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @param SourceInterface $object
     *
     * @return integer[]
     */
    public function getObjectPayload($object)
    {
        return [$object->getId()];
    }

    /**
     * @param SourceInterface $object
     *
     * @return bool
     */
    public function supportsObject($object)
    {
        return $object instanceof SourceInterface;
    }

    /**
     * @param array $payload Payload containing the source id
     *
     * @return boolean
     */
    public function execute(array $payload)
    {
        list($sourceId) = $payload;

        if (null === $source = $this->findSource($sourceId)) {
            $this->getLogger()->info(sprintf('Could not find source with id %d', $sourceId));

            return false;
        }

        if ($source->isBlocked()) {
            $this->getLogger()->debug('Source is blocked');

            $this->processor->unlink($source);

            return false;
        }

        // reset messages
        $source->setMessages([]);

        try {
            // link the source first before processing it
            $linked = $this->processor->isLinked($source);
            if (!$linked) {
                $this->logger->debug('Linking source first');
                $this->processor->link($source);
            }

            $this->processor->process($source);

            // if the source was unlinked, flush it now
            if (!$linked) {
                $this->sourceManager->flush($source);
            }

            return true;
        } catch (SourceLinkException $e) {
            $this->setMessage(
                $source,
                'link',
                sprintf('Could not link source (%d): %s', $sourceId, $e->getMessage())
            );
        } catch (SourceProcessException $e) {
            $this->setMessage(
                $source,
                'process',
                sprintf('Error while processing source (%d): %s', $sourceId, $e->getMessage())
            );
        }

        foreach ($source->getMessages() as $key => $messages) {
            foreach ($messages as $level => $message) {
                $this->logger->log($level, sprintf('[%s] %s', $key, $message));
            }
        }

        return false;
    }

    /**
     * @param integer $sourceId
     *
     * @return SourceInterface
     */
    protected function findSource($sourceId)
    {
        return $this->sourceManager->findById($sourceId);
    }

    /**
     * Sets message for a specific key, while preserving other keys
     *
     * @param SourceInterface $source
     * @param string          $key
     * @param string          $message
     * @param string          $level
     */
    protected function setMessage(SourceInterface $source, $key, $message, $level = LogLevel::ERROR)
    {
        $messages = $source->getMessages();
        $messages[$key][$level] = $message;

        $source->setMessages($messages);
    }
}
