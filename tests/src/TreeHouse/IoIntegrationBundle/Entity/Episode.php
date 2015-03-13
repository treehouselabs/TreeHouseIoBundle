<?php

namespace TreeHouse\IoIntegrationBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use TreeHouse\IoBundle\Model\SourceInterface;

/**
 * @ORM\Entity
 * @ORM\Table
 */
class Episode
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
     * @var integer
     *
     * @ORM\Column(type="integer", unique=true)
     */
    protected $number;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    protected $title;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    protected $summary;

    /**
     * @var string
     *
     * @ORM\Column(type="text")
     */
    protected $body;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    protected $duration;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    protected $imageUrl;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    protected $audioUrl;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     */
    protected $datetimePublished;

    /**
     * @var Author
     *
     * @ORM\ManyToOne(targetEntity="Author", inversedBy="episodes", cascade={"persist"})
     */
    protected $author;

    /**
     * @var ArrayCollection|SourceInterface[]
     *
     * @ORM\OneToMany(targetEntity="Source", mappedBy="episode", cascade={"persist"})
     */
    protected $sources;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->sources = new ArrayCollection();
    }
    /**
     * Set id
     *
     * @param integer $id
     *
     * @return Episode
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param integer $number
     *
     * @return $this
     */
    public function setNumber($number)
    {
        $this->number = $number;

        return $this;
    }

    /**
     * @return integer
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * Set title
     *
     * @param string $title
     *
     * @return Episode
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set summary
     *
     * @param string $summary
     *
     * @return Episode
     */
    public function setSummary($summary)
    {
        $this->summary = $summary;

        return $this;
    }

    /**
     * Get summary
     *
     * @return string
     */
    public function getSummary()
    {
        return $this->summary;
    }

    /**
     * Set body
     *
     * @param string $body
     *
     * @return Episode
     */
    public function setBody($body)
    {
        $this->body = $body;

        return $this;
    }

    /**
     * Get body
     *
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Set duration
     *
     * @param integer $duration
     *
     * @return Episode
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;

        return $this;
    }

    /**
     * Get duration
     *
     * @return integer
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * Set imageUrl
     *
     * @param string $imageUrl
     *
     * @return Episode
     */
    public function setImageUrl($imageUrl)
    {
        $this->imageUrl = $imageUrl;

        return $this;
    }

    /**
     * Get imageUrl
     *
     * @return string
     */
    public function getImageUrl()
    {
        return $this->imageUrl;
    }

    /**
     * Set audioUrl
     *
     * @param string $audioUrl
     *
     * @return Episode
     */
    public function setAudioUrl($audioUrl)
    {
        $this->audioUrl = $audioUrl;

        return $this;
    }

    /**
     * Get audioUrl
     *
     * @return string
     */
    public function getAudioUrl()
    {
        return $this->audioUrl;
    }

    /**
     * Set datetimePublished
     *
     * @param \DateTime $datetimePublished
     *
     * @return Episode
     */
    public function setDatetimePublished(\DateTime $datetimePublished)
    {
        $this->datetimePublished = $datetimePublished;

        return $this;
    }

    /**
     * Get datetimePublished
     *
     * @return \DateTime
     */
    public function getDatetimePublished()
    {
        return $this->datetimePublished;
    }

    /**
     * Set author
     *
     * @param Author $author
     *
     * @return Episode
     */
    public function setAuthor(Author $author = null)
    {
        $this->author = $author;

        return $this;
    }

    /**
     * Get author
     *
     * @return Author
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * @param SourceInterface $source
     *
     * @return $this
     */
    public function addSource(SourceInterface $source)
    {
        $this->sources[] = $source;

        return $this;
    }

    /**
     * @param SourceInterface $source
     */
    public function removeSource(SourceInterface $source)
    {
        $this->sources->removeElement($source);
    }

    /**
     * @return SourceInterface[]|ArrayCollection
     */
    public function getSources()
    {
        return $this->sources;
    }
}
