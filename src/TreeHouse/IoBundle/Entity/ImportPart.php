<?php

namespace TreeHouse\IoBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table
 * @ORM\Entity(repositoryClass="ImportPartRepository")
 */
class ImportPart
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    protected $position;

    /**
     * @var array $transportConfig
     *
     * @ORM\Column(type="json_array")
     */
    protected $transportConfig;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $process;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $error;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    protected $retries;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $datetimeScheduled;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $datetimeStarted;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $datetimeEnded;

    /**
     * @var Import
     *
     * @ORM\ManyToOne(targetEntity="Import", inversedBy="parts")
     */
    protected $import;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->retries = 1;
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param integer $position
     *
     * @return $this
     */
    public function setPosition($position)
    {
        $this->position = $position;

        return $this;
    }

    /**
     * @return integer
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @param array $transportConfig
     *
     * @return $this
     */
    public function setTransportConfig($transportConfig)
    {
        $this->transportConfig = $transportConfig;

        return $this;
    }

    /**
     * @return array
     */
    public function getTransportConfig()
    {
        return $this->transportConfig;
    }

    /**
     * @param mixed $process
     *
     * @return $this
     */
    public function setProcess($process)
    {
        $this->process = $process;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getProcess()
    {
        return $this->process;
    }

    /**
     * @param string $error
     *
     * @return $this
     */
    public function setError($error)
    {
        $this->error = $error;

        return $this;
    }

    /**
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @param integer $retries
     *
     * @return $this
     */
    public function setRetries($retries)
    {
        $this->retries = $retries;

        return $this;
    }

    /**
     * @return integer
     */
    public function getRetries()
    {
        return $this->retries;
    }

    /**
     * @param \DateTime $datetimeScheduled
     *
     * @return $this
     */
    public function setDatetimeScheduled(\DateTime $datetimeScheduled = null)
    {
        $this->datetimeScheduled = $datetimeScheduled;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDatetimeScheduled()
    {
        return $this->datetimeScheduled;
    }

    /**
     * @param \DateTime $datetimeStarted
     *
     * @return $this
     */
    public function setDatetimeStarted(\DateTime $datetimeStarted = null)
    {
        $this->datetimeStarted = $datetimeStarted;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDatetimeStarted()
    {
        return $this->datetimeStarted;
    }

    /**
     * @param \DateTime $datetimeEnded
     *
     * @return $this
     */
    public function setDatetimeEnded(\DateTime $datetimeEnded = null)
    {
        $this->datetimeEnded = $datetimeEnded;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDatetimeEnded()
    {
        return $this->datetimeEnded;
    }

    /**
     * @param Import $import
     *
     * @return $this
     */
    public function setImport(Import $import = null)
    {
        $this->import = $import;

        return $this;
    }

    /**
     * @return Import
     */
    public function getImport()
    {
        return $this->import;
    }

    /**
     * @return boolean
     */
    public function isStarted()
    {
        return !is_null($this->getProcess());
    }

    /**
     * @return boolean
     */
    public function isFinished()
    {
        return !is_null($this->getDatetimeEnded());
    }

    /**
     * @throws \InvalidArgumentException When the part does not have a valid pid
     *
     * @return boolean
     */
    public function isRunning()
    {
        if (null === $pid = $this->getProcess()) {
            return false;
        }

        if (!$pid) {
            throw new \InvalidArgumentException(
                sprintf('Import part does not have a valid pid: %s', json_encode($pid))
            );
        }

        // kill signal 0: check whether a process is running.
        // see http://www.php.net/manual/en/function.posix-kill.php#82560
        return posix_kill($pid, 0);
    }
}
