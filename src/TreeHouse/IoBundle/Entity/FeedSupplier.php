<?php

namespace TreeHouse\IoBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table
 * @ORM\Entity
 */
class FeedSupplier
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
    protected $title;

    /**
     * @var ArrayCollection|Feed[]
     *
     * @ORM\OneToMany(targetEntity="Feed", mappedBy="supplier")
     */
    protected $feeds;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->feeds = new ArrayCollection();
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $title
     *
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param Feed $feed
     *
     * @return $this
     */
    public function addFeed(Feed $feed)
    {
        $this->feeds[] = $feed;

        return $this;
    }

    /**
     * @param Feed $feed
     */
    public function removeFeed(Feed $feed)
    {
        $this->feeds->removeElement($feed);
    }

    /**
     * @return ArrayCollection|Feed[]
     */
    public function getFeeds()
    {
        return $this->feeds;
    }
}
