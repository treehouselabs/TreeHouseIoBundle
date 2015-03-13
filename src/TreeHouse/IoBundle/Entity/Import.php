<?php

namespace TreeHouse\IoBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="ImportRepository")
 * @ORM\Table
 */
class Import
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
     * @var boolean
     *
     * @ORM\Column(name="forced", type="boolean")
     */
    protected $forced;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean")
     */
    protected $partial;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $skipped;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $success;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $failed;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $erroredParts;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $datetimeScheduled;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $datetimeStarted;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $datetimeEnded;

    /**
     * @var ArrayCollection|ImportPart[]
     *
     * @ORM\OneToMany(targetEntity="ImportPart", mappedBy="import", cascade={"persist", "remove"})
     */
    protected $parts;

    /**
     * @var Feed
     *
     * @ORM\ManyToOne(targetEntity="Feed", inversedBy="imports")
     */
    protected $feed;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->parts = new ArrayCollection();

        $this->skipped    = 0;
        $this->success = 0;
        $this->failed     = 0;
        $this->erroredParts    = 0;
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param boolean $forced
     *
     * @return $this
     */
    public function setForced($forced)
    {
        $this->forced = $forced;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isForced()
    {
        return $this->forced;
    }

    /**
     * @param boolean $partial
     *
     * @return $this
     */
    public function setPartial($partial)
    {
        $this->partial = $partial;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isPartial()
    {
        return $this->partial;
    }

    /**
     * @return integer
     */
    public function getTotalNumberOfItems()
    {
        return $this->getNumberOfProcessedItems() + $this->getSkipped();
    }

    /**
     * @return integer
     */
    public function getNumberOfProcessedItems()
    {
        return $this->getSuccess() + $this->getFailed();
    }

    /**
     * @param integer $skipped
     *
     * @return $this
     */
    public function setSkipped($skipped)
    {
        $this->skipped = $skipped;

        return $this;
    }

    /**
     * @return integer
     */
    public function getSkipped()
    {
        return $this->skipped;
    }

    /**
     * @param integer $success
     *
     * @return $this
     */
    public function setSuccess($success)
    {
        $this->success = $success;

        return $this;
    }

    /**
     * @return integer
     */
    public function getSuccess()
    {
        return $this->success;
    }

    /**
     * @param integer $failed
     *
     * @return $this
     */
    public function setFailed($failed)
    {
        $this->failed = $failed;

        return $this;
    }

    /**
     * @return integer
     */
    public function getFailed()
    {
        return $this->failed;
    }

    /**
     * @param integer $erroredParts
     *
     * @return $this
     */
    public function setErroredParts($erroredParts)
    {
        $this->erroredParts = $erroredParts;

        return $this;
    }

    /**
     * @return integer
     */
    public function getErroredParts()
    {
        return $this->erroredParts;
    }

    /**
     * @param \DateTime $datetimeScheduled
     *
     * @return $this
     */
    public function setDatetimeScheduled(\DateTime $datetimeScheduled)
    {
        $this->datetimeScheduled = $datetimeScheduled;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDatetimeScheduled()
    {
        return $this->datetimeScheduled;
    }

    /**
     * @param \DateTime $datetimeStarted
     *
     * @return $this
     */
    public function setDatetimeStarted(\DateTime $datetimeStarted)
    {
        $this->datetimeStarted = $datetimeStarted;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDatetimeStarted()
    {
        return $this->datetimeStarted;
    }

    /**
     * @param \DateTime $datetimeEnded
     *
     * @return $this
     */
    public function setDatetimeEnded(\DateTime $datetimeEnded)
    {
        $this->datetimeEnded = $datetimeEnded;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDatetimeEnded()
    {
        return $this->datetimeEnded;
    }

    /**
     * @param Feed $feed
     *
     * @return $this
     */
    public function setFeed(Feed $feed = null)
    {
        $this->feed = $feed;

        return $this;
    }

    /**
     * @return Feed
     */
    public function getFeed()
    {
        return $this->feed;
    }

    /**
     * @param ImportPart $parts
     *
     * @return $this
     */
    public function addPart(ImportPart $parts)
    {
        $this->parts[] = $parts;

        return $this;
    }

    /**
     * @param ImportPart $parts
     */
    public function removePart(ImportPart $parts)
    {
        $this->parts->removeElement($parts);
    }

    /**
     * @return ArrayCollection|ImportPart[]
     */
    public function getParts()
    {
        return $this->parts;
    }

    /**
     * @return boolean
     */
    public function isStarted()
    {
        return !is_null($this->getDatetimeStarted());
    }

    /**
     * @return boolean
     */
    public function isFinished()
    {
        return !is_null($this->getDatetimeEnded());
    }

    /**
     * Checks if any of the parts of the given import has an error
     *
     * @return boolean
     */
    public function hasErrors()
    {
        return ($this->getErroredParts() > 0 || $this->getParts()->isEmpty());
    }
}
