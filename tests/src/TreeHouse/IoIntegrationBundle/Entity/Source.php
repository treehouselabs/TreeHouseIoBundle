<?php

namespace TreeHouse\IoIntegrationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints\Url;
use TreeHouse\IoBundle\Entity\Feed;
use TreeHouse\IoBundle\Model\SourceInterface;

/**
 * @ORM\Entity(repositoryClass="TreeHouse\IoBundle\Entity\SourceRepository")
 * @ORM\Table
 * @ORM\HasLifecycleCallbacks
 */
class Source implements SourceInterface
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    protected $originalId;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @Url
     */
    protected $originalUrl;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    protected $blocked;

    /**
     * @var array
     *
     * @ORM\Column(type="json_array", nullable=true)
     */
    protected $data;

    /**
     * @var array
     *
     * @ORM\Column(type="text", nullable=true)
     */
    protected $rawData;

    /**
     * @var array
     *
     * @ORM\Column(type="json_array", nullable=true)
     */
    protected $messages;

    /**
     * @var Feed
     *
     * @ORM\ManyToOne(targetEntity="TreeHouse\IoBundle\Entity\Feed", inversedBy="sources")
     */
    protected $feed;

    /**
     * @var Episode
     *
     * @ORM\ManyToOne(targetEntity="Episode", inversedBy="sources")
     */
    protected $episode;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     */
    protected $datetimeCreated;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     */
    protected $datetimeModified;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     */
    protected $datetimeLastVisited;

    /**
     * @param int
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
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
    public function setRawData($rawData)
    {
        $this->rawData = $rawData;

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
    public function getOrigin()
    {
        return $this->feed->getOrigin();
    }

    /**
     * @param Episode $episode
     *
     * @return $this
     */
    public function setEpisode($episode)
    {
        $this->episode = $episode;

        return $this;
    }

    /**
     * @return Episode
     */
    public function getEpisode()
    {
        return $this->episode;
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
     * @ORM\PrePersist()
     */
    public function prePersist()
    {
        $this->datetimeCreated = new \DateTime();
    }
}
