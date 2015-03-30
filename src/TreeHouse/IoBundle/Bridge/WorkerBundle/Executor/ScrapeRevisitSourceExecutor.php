<?php

namespace TreeHouse\IoBundle\Bridge\WorkerBundle\Executor;

use FM\WorkerBundle\Monolog\LoggerAggregate;
use FM\WorkerBundle\Queue\JobExecutor;
use FM\WorkerBundle\Queue\ObjectPayloadInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use TreeHouse\IoBundle\Model\SourceInterface;
use TreeHouse\IoBundle\Scrape\Exception\CrawlException;
use TreeHouse\IoBundle\Scrape\SourceRevisitor;
use TreeHouse\IoBundle\Source\SourceManagerInterface;

class ScrapeRevisitSourceExecutor extends JobExecutor implements ObjectPayloadInterface, LoggerAggregate
{
    const NAME = 'scrape.source.revisit';

    /**
     * @var SourceManagerInterface
     */
    protected $sourceManager;

    /**
     * @var SourceRevisitor
     */
    protected $revisitor;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param SourceManagerInterface $sourceManager
     * @param SourceRevisitor        $revisitor
     * @param LoggerInterface        $logger
     */
    public function __construct(SourceManagerInterface $sourceManager, SourceRevisitor $revisitor, LoggerInterface $logger)
    {
        $this->sourceManager = $sourceManager;
        $this->revisitor     = $revisitor;
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
     * @inheritdoc
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @inheritdoc
     */
    public function supportsObject($object)
    {
        return $object instanceof SourceInterface;
    }

    /**
     * @inheritdoc
     *
     * @param SourceInterface $object
     */
    public function getObjectPayload($object)
    {
        return [$object->getId()];
    }

    /**
     * @inheritdoc
     */
    public function execute(array $payload)
    {
        $resolver = $this->getOptionsResolver();
        $options  = $resolver->resolve($payload);

        if (null === $source = $this->findSource($options[0])) {
            $this->logger->error(sprintf('Source %d not found', $options[0]));

            return false;
        }

        try {
            $this->revisitor->revisit($source, true);

            return true;
        } catch (CrawlException $e) {
            $this->logger->error($e->getMessage(), ['url' => $e->getUrl()]);

            return false;
        }
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
     * @return OptionsResolver
     */
    protected function getOptionsResolver()
    {
        $resolver = new OptionsResolver();
        $resolver->setRequired([0]);
        $resolver->setAllowedTypes(0, ['numeric']);

        return $resolver;
    }
}
