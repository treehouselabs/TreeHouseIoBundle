<?php

namespace TreeHouse\IoBundle\Bridge\WorkerBundle\Executor;

use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidArgumentException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use TreeHouse\IoBundle\Model\SourceInterface;
use TreeHouse\IoBundle\Scrape\Exception\CrawlException;
use TreeHouse\IoBundle\Scrape\Exception\RateLimitException;
use TreeHouse\IoBundle\Scrape\SourceRevisitor;
use TreeHouse\IoBundle\Source\SourceManagerInterface;
use TreeHouse\WorkerBundle\Exception\RescheduleException;
use TreeHouse\WorkerBundle\Executor\AbstractExecutor;
use TreeHouse\WorkerBundle\Executor\ObjectPayloadInterface;

class ScrapeRevisitSourceExecutor extends AbstractExecutor implements ObjectPayloadInterface
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
    public function configurePayload(OptionsResolver $resolver)
    {
        $resolver->setRequired(0);
        $resolver->setAllowedTypes(0, 'numeric');
        $resolver->setNormalizer(0, function (Options $options, $value) {
            if (null === $source = $this->findSource($value)) {
                throw new InvalidArgumentException(sprintf('Could not find source with id %d', $value));
            }

            return $source;
        });
    }

    /**
     * @inheritdoc
     */
    public function execute(array $payload)
    {
        /** @var SourceInterface $source */
        list($source) = $payload;

        try {
            $this->revisitor->revisit($source, true);

            return true;
        } catch (RateLimitException $e) {
            $re = new RescheduleException();

            if ($date = $e->getRetryDate()) {
                $re->setRescheduleDate($date);
            }

            throw $re;
        } catch (CrawlException $e) {
            $this->logger->error($e->getMessage(), ['url' => $e->getUrl()]);

            return false;
        }
    }

    /**
     * @param int $sourceId
     *
     * @return SourceInterface
     */
    protected function findSource($sourceId)
    {
        return $this->sourceManager->findById($sourceId);
    }
}
