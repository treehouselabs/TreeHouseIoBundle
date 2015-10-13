<?php

namespace TreeHouse\IoBundle\Model;

use Doctrine\Common\Collections\Collection;
use TreeHouse\IoBundle\Entity\Feed;

interface OriginInterface
{
    /**
     * Get id.
     *
     * @return int
     */
    public function getId();

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return $this
     */
    public function setName($name);

    /**
     * Get name.
     *
     * @return string
     */
    public function getName();

    /**
     * Set title.
     *
     * @param string $title
     *
     * @return $this
     */
    public function setTitle($title);

    /**
     * Get title.
     *
     * @return string
     */
    public function getTitle();

    /**
     * Set priority.
     *
     * @param int $priority
     *
     * @return $this
     */
    public function setPriority($priority);

    /**
     * Get priority.
     *
     * @return int
     */
    public function getPriority();

    /**
     * Add feeds.
     *
     * @param Feed $feeds
     *
     * @return $this
     */
    public function addFeed(Feed $feeds);

    /**
     * Remove feeds.
     *
     * @param Feed $feeds
     */
    public function removeFeed(Feed $feeds);

    /**
     * Get feeds.
     *
     * @return Collection|Feed[]
     */
    public function getFeeds();
}
