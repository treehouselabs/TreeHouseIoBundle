<?php

namespace TreeHouse\IoBundle\Model;

use TreeHouse\IoBundle\Entity\Feed;

interface SourceInterface
{
    /**
     * Get id
     *
     * @return integer
     */
    public function getId();

    /**
     * Set originalId
     *
     * @param string $originalId
     *
     * @return SourceInterface
     */
    public function setOriginalId($originalId);

    /**
     * Get originalId
     *
     * @return string
     */
    public function getOriginalId();

    /**
     * Set originalUrl
     *
     * @param string $originalUrl
     *
     * @return SourceInterface
     */
    public function setOriginalUrl($originalUrl);

    /**
     * Get originalUrl
     *
     * @return string
     */
    public function getOriginalUrl();

    /**
     * Set blocked
     *
     * @param boolean $blocked
     *
     * @return SourceInterface
     */
    public function setBlocked($blocked);

    /**
     * Get blocked
     *
     * @return boolean
     */
    public function isBlocked();

    /**
     * Set data
     *
     * @param array $data
     *
     * @return SourceInterface
     */
    public function setData($data);

    /**
     * Get data
     *
     * @return array
     */
    public function getData();

    /**
     * Set source data
     *
     * @param string $data
     *
     * @return SourceInterface
     */
    public function setSourceData($data);

    /**
     * Get source data
     *
     * @return array
     */
    public function getSourceData();

    /**
     * Set messages
     *
     * @param array $messages
     *
     * @return SourceInterface
     */
    public function setMessages($messages);

    /**
     * Get messages
     *
     * @return array
     */
    public function getMessages();

    /**
     * Set feed
     *
     * @param Feed $feed
     *
     * @return SourceInterface
     */
    public function setFeed(Feed $feed = null);

    /**
     * Get feed
     *
     * @return Feed
     */
    public function getFeed();

    /**
     * Set datetimeCreated
     *
     * @param \DateTime $datetimeCreated
     *
     * @return SourceInterface
     */
    public function setDatetimeCreated(\DateTime $datetimeCreated);

    /**
     * Get datetimeCreated
     *
     * @return \DateTime
     */
    public function getDatetimeCreated();

    /**
     * Set datetimeModified
     *
     * @param \DateTime $datetimeModified
     *
     * @return SourceInterface
     */
    public function setDatetimeModified(\DateTime $datetimeModified);

    /**
     * Get datetimeModified
     *
     * @return \DateTime
     */
    public function getDatetimeModified();

    /**
     * Set datetimeLastVisited
     *
     * @param \DateTime $datetimeLastVisited
     *
     * @return SourceInterface
     */
    public function setDatetimeLastVisited(\DateTime $datetimeLastVisited);

    /**
     * Get datetimeLastVisited
     *
     * @return \DateTime
     */
    public function getDatetimeLastVisited();
}
