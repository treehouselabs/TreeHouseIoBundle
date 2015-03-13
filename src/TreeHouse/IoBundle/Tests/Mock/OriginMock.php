<?php

namespace TreeHouse\IoBundle\Tests\Mock;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use TreeHouse\IoBundle\Entity\Feed;
use TreeHouse\IoBundle\Model\OriginInterface;
use TreeHouse\IoBundle\Model\SourceInterface;

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
     * @var Collection|SourceInterface[]
     */
    protected $sources;

    /**
     * @param integer $id
     */
    public function __construct($id)
    {
        $this->id      = $id;
        $this->feeds   = new ArrayCollection();
        $this->sources = new ArrayCollection();
    }

    /**
     * @inherit
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @inherit
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @inherit
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @inherit
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @inherit
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @inherit
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;

        return $this;
    }

    /**
     * @inherit
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @inherit
     */
    public function addFeed(Feed $feeds)
    {
        $this->feeds[] = $feeds;

        return $this;
    }

    /**
     * @inherit
     */
    public function removeFeed(Feed $feeds)
    {
        $this->feeds->removeElement($feeds);
    }

    /**
     * @inherit
     */
    public function getFeeds()
    {
        return $this->feeds;
    }

    /**
     * @inherit
     */
    public function addSource(SourceInterface $sources)
    {
        $this->sources[] = $sources;

        return $this;
    }

    /**
     * @inherit
     */
    public function removeSource(SourceInterface $sources)
    {
        $this->sources->removeElement($sources);
    }

    /**
     * @inherit
     */
    public function getSources()
    {
        return $this->sources;
    }
}
