<?php

namespace Doctrs\SonataImportBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ImportLog
 *
 * @ORM\Table("ext_sonata_import_log")
 * @ORM\Entity(repositoryClass="Doctrs\SonataImportBundle\Repository\DefaultRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class ImportLog
{
    const STATUS_SUCCESS = 1;
    const STATUS_EXISTS = 2;
    const STATUS_ERROR = 3;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="ts", type="datetime")
     */
    private $ts;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="integer")
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="message", type="text", nullable=true)
     */
    private $message;

    /**
     * @var string
     *
     * @ORM\Column(name="line", type="string", length=255)
     */
    private $line;

    /**
     * @var string
     *
     * @ORM\ManyToOne(targetEntity="Doctrs\SonataImportBundle\Entity\UploadFile", inversedBy="importLog")
     */
    private $uploadFile;

    /**
     * @var string
     *
     * @ORM\Column(name="foreign_id", type="text", nullable=true)
     */
    private $foreignId;


    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get ts
     *
     * @return \DateTime 
     */
    public function getTs()
    {
        return $this->ts;
    }

    /**
     * Set status
     *
     * @param integer $status
     * @return ImportLog
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return integer 
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set message
     *
     * @param string $message
     * @return ImportLog
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get message
     *
     * @return string 
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @return mixed
     */
    public function messageEncode() {
        return json_decode($this->message);
    }

    /**
     * Set line
     *
     * @param string $line
     * @return ImportLog
     */
    public function setLine($line)
    {
        $this->line = $line;

        return $this;
    }

    /**
     * Get line
     *
     * @return string 
     */
    public function getLine()
    {
        return $this->line;
    }

    /**
     * Set uploadFile
     *
     * @param string $uploadFile
     * @return ImportLog
     */
    public function setUploadFile($uploadFile)
    {
        $this->uploadFile = $uploadFile;

        return $this;
    }

    /**
     * Get uploadFile
     *
     * @return string 
     */
    public function getUploadFile()
    {
        return $this->uploadFile;
    }


    /**
     * @ORM\PreUpdate()
     * @ORM\PrePersist()
     */
    public function prePersistUpdate() {
        $this->ts = new \DateTime();
    }

    /**
     * @param $foreignId
     * @return ImportLog
     */
    public function setForeignId($foreignId) {
        $this->foreignId = $foreignId;

        return $this;
    }

    /**
     * @return int
     */
    public function getForeignId() {
        return $this->foreignId;
    }

    /**
     * @return string
     */
    public function __toString() {
        return (string)$this->message;
    }
}
