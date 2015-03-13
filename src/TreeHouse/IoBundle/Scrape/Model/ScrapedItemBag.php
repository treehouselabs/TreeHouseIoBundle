<?php

namespace FM\IoBundle\Scrape\Model;

use FM\IoBundle\Entity\Scraper;
use Symfony\Component\HttpFoundation\ParameterBag;

class ScrapedItemBag extends ParameterBag
{
    /**
     * @var Scraper
     */
    protected $scraper;

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
     * @param Scraper $scraper
     * @param string  $originalUrl
     * @param array   $parameters
     */
    public function __construct(Scraper $scraper, $originalUrl, array $parameters = [])
    {
        parent::__construct($parameters);

        $this->scraper     = $scraper;
        $this->originalUrl = $originalUrl;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return sprintf('%s:%s', $this->scraper->getOrigin()->getName(), $this->originalUrl);
    }

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
