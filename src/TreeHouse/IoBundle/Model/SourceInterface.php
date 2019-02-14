<?php

namespace TreeHouse\IoBundle\Model;

use TreeHouse\IoBundle\Entity\Feed;
use TreeHouse\IoBundle\Entity\Scraper;

interface SourceInterface
{
    /**
     * Get id.
     *
     * @return int
     */
    public function getId();

    /**
     * Set originalId.
     *
     * @param string $originalId
     *
     * @return $this
     */
    public function setOriginalId($originalId);

    /**
     * Get originalId.
     *
     * @return string
     */
    public function getOriginalId();

    /**
     * Set originalUrl.
     *
     * @param string $originalUrl
     *
     * @return $this
     */
    public function setOriginalUrl($originalUrl);

    /**
     * Get originalUrl.
     *
     * @return string
     */
    public function getOriginalUrl();

    /**
     * Set blocked.
     *
     * @param bool $blocked
     *
     * @return $this
     */
    public function setBlocked($blocked);

    /**
     * Get blocked.
     *
     * @return bool
     */
    public function isBlocked();

    /**
     * Set data.
     *
     * @param array $data
     *
     * @return $this
     */
    public function setData($data);

    /**
     * Get data.
     *
     * @return array
     */
    public function getData();

    /**
     * Set raw source data.
     *
     * @param string $rawData
     *
     * @return $this
     */
    public function setRawData($rawData);

    /**
     * Get raw source data.
     *
     * @return array
     */
    public function getRawData();

    /**
     * Set messages.
     *
     * @param array $messages
     *
     * @return $this
     */
    public function setMessages($messages);

    /**
     * Get messages.
     *
     * @return array
     */
    public function getMessages();

    /**
     * Set feed.
     *
     * @param Feed $feed
     *
     * @return $this
     */
    public function setFeed(Feed $feed = null);

    /**
     * Get feed.
     *
     * @return Feed
     */
    public function getFeed();

    /**
     * Set scraper.
     *
     * @param Scraper $scraper
     *
     * @return $this
     */
    public function setScraper(Scraper $scraper = null);

    /**
     * Get scraper.
     *
     * @return Scraper
     */
    public function getScraper();

    /**
     * Get origin.
     *
     * @return OriginInterface
     */
    public function getOrigin();

    /**
     * Set datetimeCreated.
     *
     * @param \DateTime $datetimeCreated
     *
     * @return $this
     */
    public function setDatetimeCreated(\DateTime $datetimeCreated);

    /**
     * Get datetimeCreated.
     *
     * @return \DateTime
     */
    public function getDatetimeCreated();

    /**
     * Set datetimeModified.
     *
     * @param \DateTime $datetimeModified
     *
     * @return $this
     */
    public function setDatetimeModified(\DateTime $datetimeModified);

    /**
     * Get datetimeModified.
     *
     * @return \DateTime
     */
    public function getDatetimeModified();

    /**
     * Set datetimeImported.
     *
     * @param \DateTime $datetimeImported
     *
     * @return $this
     */
    public function setDatetimeImported(\DateTime $datetimeImported);

    /**
     * Get datetimeImported.
     *
     * @return \DateTime
     */
    public function getDatetimeImported();

    /**
     * Set datetimeLastVisited.
     *
     * @param \DateTime $datetimeLastVisited
     *
     * @return $this
     */
    public function setDatetimeLastVisited(\DateTime $datetimeLastVisited);

    /**
     * Get datetimeLastVisited.
     *
     * @return \DateTime
     */
    public function getDatetimeLastVisited();
}
