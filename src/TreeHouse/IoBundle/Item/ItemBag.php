<?php

namespace TreeHouse\IoBundle\Item;

use Symfony\Component\HttpFoundation\ParameterBag;

abstract class ItemBag extends ParameterBag
{
    /**
     * @var string
     */
    protected $originalId;

    /**
     * @var string
     */
    protected $originalUrl;

    /**
     * @var \DateTime
     */
    protected $datetimeModified;

    /**
     * Implementing classes must have a toString method
     *
     * @return string
     */
    abstract public function __toString();

    /**
     * @param string $originalId
     */
    public function setOriginalId($originalId)
    {
        $this->originalId = $originalId;
    }

    /**
     * @return string
     */
    public function getOriginalId()
    {
        return $this->originalId;
    }

    /**
     * @param string $originalUrl
     */
    public function setOriginalUrl($originalUrl)
    {
        $this->originalUrl = $originalUrl;
    }

    /**
     * @return string
     */
    public function getOriginalUrl()
    {
        return $this->originalUrl;
    }

    /**
     * @param \DateTime $datetimeModified
     */
    public function setDatetimeModified(\DateTime $datetimeModified = null)
    {
        $this->datetimeModified = $datetimeModified;
    }

    /**
     * @return \DateTime
     */
    public function getDatetimeModified()
    {
        return $this->datetimeModified;
    }

    /**
     * Returns all data from the item, ordered by key
     */
    public function all()
    {
        $all = parent::all();
        ksort($all);

        return $all;
    }
}
