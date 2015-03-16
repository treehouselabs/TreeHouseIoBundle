<?php

namespace TreeHouse\IoBundle\Tests\Mock;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use TreeHouse\IoBundle\Entity\Feed;
use TreeHouse\IoBundle\Model\OriginInterface;

class OriginMock implements OriginInterface
{
    /**
     * @var integer $id
     */
    protected $id;

    /**
     * @var string $name
     */
    protected $name;

    /**
     * @var string $title
     */
    protected $title;

    /**
     * @var integer $priority
     */
    protected $priority;

    /**
     * @var Collection|Feed[]
     */
    protected $feeds;

    /**
     * @param integer $id
     */
    public function __construct($id)
    {
        $this->id      = $id;
        $this->feeds   = new ArrayCollection();
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
    public function addFeed(Feed $feeds)
    {
        $this->feeds[] = $feeds;

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
