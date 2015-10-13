<?php

namespace TreeHouse\IoIntegrationBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table
 */
class Author
{
    /**
     * @var int
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
    protected $name;

    /**
     * @var ArrayCollection|Episode[]
     *
     * @ORM\OneToMany(targetEntity="Episode", mappedBy="author", cascade={"persist", "remove"})
     */
    protected $episodes;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->episodes = new ArrayCollection();
    }

    /**
     * Set id.
     *
     * @param int $id
     *
     * @return Author
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return Author
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Add episodes.
     *
     * @param Episode $episodes
     *
     * @return Author
     */
    public function addEpisode(Episode $episodes)
    {
        $this->episodes[] = $episodes;

        return $this;
    }

    /**
     * Remove episodes.
     *
     * @param Episode $episodes
     */
    public function removeEpisode(Episode $episodes)
    {
        $this->episodes->removeElement($episodes);
    }

    /**
     * Get episodes.
     *
     * @return ArrayCollection|Episode[]
     */
    public function getEpisodes()
    {
        return $this->episodes;
    }
}
