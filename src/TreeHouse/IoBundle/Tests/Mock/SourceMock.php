<?php

namespace TreeHouse\IoBundle\Tests\Mock;

use TreeHouse\IoBundle\Entity\Feed;
use TreeHouse\IoBundle\Entity\Scraper;
use TreeHouse\IoBundle\Model\OriginInterface;
use TreeHouse\IoBundle\Model\SourceInterface;

class SourceMock implements SourceInterface
{
    /**
     * @var integer $id
     */
    protected $id;

    /**
     * @var string $originalId
     */
    protected $originalId;

    /**
     * @var string $originalUrl
     */
    protected $originalUrl;

    /**
     * @var bool $blocked
     */
    protected $blocked;

    /**
     * @var array $data
     */
    protected $data;

    /**
     * @var string $rawData
     */
    protected $rawData;

    /**
     * @var array $messages
     */
    protected $messages;

    /**
     * @var \DateTime $datetimeLastVisited
     */
    protected $datetimeLastVisited;

    /**
     * @var \DateTime $datetimeModified
     */
    protected $datetimeCreated;

    /**
     * @var \DateTime $datetimeModified
     */
    protected $datetimeModified;

    /**
     * @var OriginInterface
     */
    protected $origin;

    /**
     * @var Feed
     */
    protected $feed;

    /**
     * @var Scraper
     */
    protected $scraper;

    /**
     * @param integer $id
     */
    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @inheritdoc
     */
    public function setOriginalId($originalId)
    {
        $this->originalId = $originalId;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getOriginalId()
    {
        return $this->originalId;
    }

    /**
     * @inheritdoc
     */
    public function setOriginalUrl($originalUrl)
    {
        $this->originalUrl = $originalUrl;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getOriginalUrl()
    {
        return $this->originalUrl;
    }

    /**
     * @inheritdoc
     */
    public function setBlocked($blocked)
    {
        $this->blocked = $blocked;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function isBlocked()
    {
        return $this->blocked;
    }

    /**
     * @inheritdoc
     */
    public function setData($data)
    {
        ksort($data);

        $this->data = $data;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @inheritdoc
     */
    public function setRawData($data)
    {
        $this->rawData = $data;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getRawData()
    {
        return $this->rawData;
    }

    /**
     * @inheritdoc
     */
    public function setMessages($messages)
    {
        $this->messages = $messages;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * @inheritdoc
     */
    public function setDatetimeLastVisited(\DateTime $datetimeLastVisited)
    {
        $this->datetimeLastVisited = $datetimeLastVisited;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getDatetimeLastVisited()
    {
        return $this->datetimeLastVisited;
    }

    /**
     * @inheritdoc
     */
    public function setDatetimeCreated(\DateTime $datetimeCreated)
    {
        $this->datetimeCreated = $datetimeCreated;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getDatetimeCreated()
    {
        return $this->datetimeCreated;
    }

    /**
     * @inheritdoc
     */
    public function setDatetimeModified(\DateTime $datetimeModified)
    {
        $this->datetimeModified = $datetimeModified;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getDatetimeModified()
    {
        return $this->datetimeModified;
    }

    /**
     * @inheritdoc
     */
    public function setFeed(Feed $feed = null)
    {
        $this->feed = $feed;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getFeed()
    {
        return $this->feed;
    }

    /**
     * @inheritdoc
     */
    public function setScraper(Scraper $scraper = null)
    {
        $this->scraper = $scraper;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getScraper()
    {
        return $this->scraper;
    }

    /**
     * @inheritdoc
     */
    public function getOrigin()
    {
        return $this->feed->getOrigin();
    }
}
