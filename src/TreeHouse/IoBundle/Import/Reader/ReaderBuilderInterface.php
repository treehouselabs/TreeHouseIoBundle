<?php

namespace TreeHouse\IoBundle\Import\Reader;

use TreeHouse\Feeder\Reader\ReaderInterface;
use TreeHouse\Feeder\Resource\Transformer\ResourceTransformerInterface;
use TreeHouse\IoBundle\Import\Reader\Type\ReaderTypeInterface;

interface ReaderBuilderInterface
{
    /**
     * XML reader.
     */
    const READER_TYPE_XML = 'xml';

    /**
     * JsonLines reader
     */
    const READER_TYPE_JSONLINES = 'jsonl';

    /**
     * Main resource; transformers can modify it to create the import parts.
     */
    const RESOURCE_TYPE_MAIN = 'main';

    /**
     * Part resource; transformers can modify the part resource.
     */
    const RESOURCE_TYPE_PART = 'part';

    /**
     * @param ReaderTypeInterface $type
     * @param array               $transportConfig
     * @param string              $resourceType    One of the ReaderBuilderInterface::RESOURCE_TYPE_* constants
     * @param array               $options
     *
     * @return ReaderInterface
     */
    public function build(ReaderTypeInterface $type, array $transportConfig, $resourceType, array $options);

    /**
     * Returns transformers for the main feed resource (before parts are created).
     *
     * @param ResourceTransformerInterface $transformer
     */
    public function addResourceTransformer(ResourceTransformerInterface $transformer);

    /**
     * Returns transformers for each part of the feed.
     *
     * @param ResourceTransformerInterface $transformer
     */
    public function addPartResourceTransformer(ResourceTransformerInterface $transformer);

    /**
     * @param string $type
     */
    public function setReaderType($type);
}
