<?php

namespace TreeHouse\IoIntegrationBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use TreeHouse\IoBundle\Entity\Feed;
use TreeHouse\IoBundle\Model\OriginInterface;

/**
 * @ORM\Entity
 * @ORM\Table
 */
class Origin implements OriginInterface
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    protected $title;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    protected $priority;

    /**
     * @var ArrayCollection|Feed[]
     *
     * @ORM\OneToMany(targetEntity="TreeHouse\IoBundle\Entity\Feed", mappedBy="origin", cascade={"persist", "remove"})
     */
    protected $feeds;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->feeds   = new ArrayCollection();
    }

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
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @inheritdoc
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @inheritdoc
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @inheritdoc
     */
    public function addFeed(Feed $feed)
    {
        $this->feeds->add($feed);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function removeFeed(Feed $feeds)
    {
        $this->feeds->removeElement($feeds);
    }

    /**
     * @inheritdoc
     */
    public function getFeeds()
    {
        return $this->feeds;
    }
}
