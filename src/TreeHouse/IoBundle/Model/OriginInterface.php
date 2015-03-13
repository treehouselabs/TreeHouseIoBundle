<?php

namespace TreeHouse\IoBundle\Model;

use Doctrine\Common\Collections\Collection;
use TreeHouse\IoBundle\Entity\Feed;

interface OriginInterface
{
    /**
     * Get id
     *
     * @return integer
     */
    public function getId();

    /**
     * Set name
     *
     * @param string $name
     *
     * @return OriginInterface
     */
    public function setName($name);

    /**
     * Get name
     *
     * @return string
     */
    public function getName();

    /**
     * Set title
     *
     * @param string $title
     *
     * @return OriginInterface
     */
    public function setTitle($title);

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle();

    /**
     * Set priority
     *
     * @param integer $priority
     *
     * @return OriginInterface
     */
    public function setPriority($priority);

    /**
     * Get priority
     *
     * @return integer
     */
    public function getPriority();

    /**
     * Add feeds
     *
     * @param Feed $feeds
     *
     * @return OriginInterface
     */
    public function addFeed(Feed $feeds);

    /**
     * Remove feeds
     *
     * @param Feed $feeds
     *
     * @return void
     */
    public function removeFeed(Feed $feeds);

    /**
     * Get feeds
     *
     * @return Collection|Feed[]
     */
    public function getFeeds();

    /**
     * Add sources
     *
     * @param SourceInterface $sources
     *
     * @return OriginInterface
     */
    public function addSource(SourceInterface $sources);

    /**
     * Remove sources
     *
     * @param SourceInterface $sources
     *
     * @return void
     */
    public function removeSource(SourceInterface $sources);

    /**
     * Get sources
     *
     * @return Collection|SourceInterface[]
     */
    public function getSources();
}
