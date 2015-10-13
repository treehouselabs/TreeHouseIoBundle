<?php

namespace TreeHouse\IoBundle\Import\Handler;

use Symfony\Component\Validator\Validator\ValidatorInterface;
use TreeHouse\IoBundle\Exception\ValidationException;
use TreeHouse\IoBundle\Import\Exception\FailedItemException;
use TreeHouse\IoBundle\Import\Feed\FeedItemBag;
use TreeHouse\IoBundle\Model\SourceInterface;
use TreeHouse\IoBundle\Source\Manager\CachedSourceManager;

class DoctrineHandler implements HandlerInterface
{
    /**
     * @var CachedSourceManager
     */
    protected $sourceManager;

    /**
     * @var ValidatorInterface
     */
    protected $validator;

    /**
     * @param CachedSourceManager $sourceManager
     * @param ValidatorInterface  $validator
     */
    public function __construct(CachedSourceManager $sourceManager, ValidatorInterface $validator)
    {
        $this->sourceManager = $sourceManager;
        $this->validator = $validator;
    }

    /**
     * @inheritdoc
     */
    public function handle(FeedItemBag $item)
    {
        // get source and set the data to it
        $source = $this->sourceManager->findSourceByFeedOrCreate(
            $item->getFeed(),
            $item->getOriginalId(),
            $item->getOriginalUrl()
        );

        // save data
        $source->setData($item->all());

        try {
            $this->validate($source);
            $this->sourceManager->persist($source);
            $this->sourceManager->flush($source);

            return $source;
        } catch (\Exception $exception) {
            $this->sourceManager->detach($source);

            throw new FailedItemException($source, $exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * @inheritdoc
     */
    public function flush()
    {
        $this->sourceManager->flush();
    }

    /**
     * @inheritdoc
     */
    public function clear()
    {
        $this->sourceManager->clear();
    }

    /**
     * @param SourceInterface $source
     *
     * @throws ValidationException
     */
    protected function validate(SourceInterface $source)
    {
        $violations = $this->validator->validate($source);

        if ($violations->count()) {
            throw ValidationException::create($violations);
        }
    }
}
