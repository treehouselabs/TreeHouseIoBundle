<?php

namespace TreeHouse\IoBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use TreeHouse\IoBundle\Model\OriginInterface;
use TreeHouse\IoBundle\Model\SourceInterface;

/**
 * @ORM\Entity(repositoryClass="ScraperRepository")
 * @ORM\Table
 */
class Scraper
{
    /**
     * @var integer $id
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * The frequency to start the scraper, in hours
     *
     * @var integer $startFrequency
     *
     * @ORM\Column(type="integer")
     */
    protected $startFrequency;

    /**
     * The frequency to revisit sources, in hours
     *
     * @var integer $revisitFrequency
     *
     * @ORM\Column(type="integer")
     */
    protected $revisitFrequency;

    /**
     * One of the configured crawler types
     *
     * @var string $type
     *
     * @ORM\Column(type="string")
     */
    protected $crawler;

    /**
     * Options to be passed to the crawler type
     *
     * @var array $options
     *
     * @ORM\Column(type="json_array", nullable=true)
     */
    protected $crawlerOptions;

    /**
     * One of the configured parser types
     *
     * @var string $type
     *
     * @ORM\Column(type="string")
     */
    protected $parser;

    /**
     * Options to be passed to the parser type
     *
     * @var array $options
     *
     * @ORM\Column(type="json_array", nullable=true)
     */
    protected $parserOptions;

    /**
     * One of the configured handlers
     *
     * @var string $type
     *
     * @ORM\Column(type="string")
     */
    protected $handler;

    /**
     * The root url
     *
     * @var string $url
     *
     * @ORM\Column(type="string")
     */
    protected $url;

    /**
     * Can contain key/value pairs to be used as defaults if the scraped pages doesn't supply them
     *
     * @var array $defaultValues
     *
     * @ORM\Column(type="json_array", nullable=true)
     */
    protected $defaultValues;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $datetimeLastStarted;

    /**
     * @var OriginInterface
     *
     * @ORM\ManyToOne(targetEntity="TreeHouse\IoBundle\Model\OriginInterface", inversedBy="scrapers", cascade={"persist"})
     */
    protected $origin;

    /**
     * @var SourceInterface
     *
     * @ORM\OneToMany(targetEntity="TreeHouse\IoBundle\Model\SourceInterface", mappedBy="scraper", cascade={"persist", "remove"})
     */
    protected $sources;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->sources = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->url;
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param integer $startFrequency
     *
     * @return $this
     */
    public function setStartFrequency($startFrequency)
    {
        $this->startFrequency = $startFrequency;

        return $this;
    }

    /**
     * @return integer
     */
    public function getStartFrequency()
    {
        return $this->startFrequency;
    }

    /**
     * @param integer $revisitFrequency
     *
     * @return $this
     */
    public function setRevisitFrequency($revisitFrequency)
    {
        $this->revisitFrequency = $revisitFrequency;

        return $this;
    }

    /**
     * @return integer
     */
    public function getRevisitFrequency()
    {
        return $this->revisitFrequency;
    }

    /**
     * @param string $crawler
     *
     * @return $this
     */
    public function setCrawler($crawler)
    {
        $this->crawler = $crawler;

        return $this;
    }

    /**
     * @return string
     */
    public function getCrawler()
    {
        return $this->crawler;
    }

    /**
     * @param array $crawlerOptions
     *
     * @return $this
     */
    public function setCrawlerOptions(array $crawlerOptions)
    {
        $this->crawlerOptions = $crawlerOptions;

        return $this;
    }

    /**
     * @return array
     */
    public function getCrawlerOptions()
    {
        return $this->crawlerOptions;
    }

    /**
     * @param string $parser
     *
     * @return $this
     */
    public function setParser($parser)
    {
        $this->parser = $parser;

        return $this;
    }

    /**
     * @return string
     */
    public function getParser()
    {
        return $this->parser;
    }

    /**
     * @param array $parserOptions
     *
     * @return $this
     */
    public function setParserOptions(array $parserOptions)
    {
        $this->parserOptions = $parserOptions;

        return $this;
    }

    /**
     * @return array
     */
    public function getParserOptions()
    {
        return $this->parserOptions;
    }

    /**
     * @param string $handler
     *
     * @return $this
     */
    public function setHandler($handler)
    {
        $this->handler = $handler;

        return $this;
    }

    /**
     * @return string
     */
    public function getHandler()
    {
        return $this->handler;
    }

    /**
     * @param string $url
     *
     * @return $this
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param array $defaultValues
     *
     * @return $this
     */
    public function setDefaultValues(array $defaultValues)
    {
        $this->defaultValues = $defaultValues;

        return $this;
    }

    /**
     * @return array
     */
    public function getDefaultValues()
    {
        return $this->defaultValues;
    }

    /**
     * @param \DateTime $datetimeLastStarted
     *
     * @return $this
     */
    public function setDatetimeLastStarted(\DateTime $datetimeLastStarted = null)
    {
        $this->datetimeLastStarted = $datetimeLastStarted;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDatetimeLastStarted()
    {
        return $this->datetimeLastStarted;
    }

    /**
     * @param OriginInterface $origin
     *
     * @return $this
     */
    public function setOrigin(OriginInterface $origin = null)
    {
        $this->origin = $origin;

        return $this;
    }

    /**
     * @return OriginInterface
     */
    public function getOrigin()
    {
        return $this->origin;
    }

    /**
     * @param SourceInterface $source
     *
     * @return $this
     */
    public function addSource(SourceInterface $source)
    {
        $this->sources[] = $source;

        return $this;
    }

    /**
     * @param SourceInterface $source
     */
    public function removeSource(SourceInterface $source)
    {
        $this->sources->removeElement($source);
    }

    /**
     * @return SourceInterface[]|ArrayCollection
     */
    public function getSources()
    {
        return $this->sources;
    }
}
