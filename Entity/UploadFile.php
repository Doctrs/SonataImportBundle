<?php

namespace Doctrs\SonataImportBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * UploadFile
 *
 * @ORM\Table("ext_sonata_import_file")
 * @ORM\Entity(repositoryClass="Doctrs\SonataImportBundle\Repository\DefaultRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class UploadFile
{

    const STATUS_LOAD = 1;
    const STATUS_SUCCESS = 2;
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
     * @var string
     *
     * @ORM\Column(name="file", type="string")
     * @var UploadedFile
     */
    private $file;

    /**
     * @var string
     *
     * @ORM\Column(name="encode", type="string")
     */
    private $encode;

    /**
     * @var string
     *
     * @ORM\Column(name="loader_class", type="string")
     */
    private $loaderClass;

    /**
     * @var string
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
     * Set file
     *
     * @param string $file
     * @return UploadFile
     */
    public function setFile($file)
    {
        $this->file = $file;

        return $this;
    }

    /**
     * Get file
     *
     * @return UploadedFile|null
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function prePersistUpdate() {
        if (!$this->status) {
            $this->status = self::STATUS_LOAD;
        }
        $this->ts = new \DateTime();
    }

    /**
     * @param $encode
     * @return UploadFile
     */
    public function setEncode($encode) {
        $this->encode = $encode;

        return $this;
    }

    /**
     * @return string
     */
    public function getEncode() {
        return $this->encode;
    }

    /**
     * @param $message
     * @return UploadFile
     */
    public function setMessage($message) {
        $this->message = $message;

        return $this;
    }

    /**
     * @return string
     */
    public function getMessage() {
        return $this->message;
    }

    /**
     * @param $status
     * @return UploadFile
     */
    public function setStatus($status) {
        $this->status = $status;

        return $this;
    }

    /**
     * @return string
     */
    public function getStatus() {
        return $this->status;
    }

    /**
     * @param $loaderClass
     * @return $this
     */
    public function setLoaderClass($loaderClass) {
        $this->loaderClass = $loaderClass;

        return $this;
    }

    /**
     * @return string
     */
    public function getLoaderClass() {
        return $this->loaderClass;
    }

    public function move($uploadDir) {
        $file = $this->getFile();
        $fileName = md5(uniqid() . time()) . '.' . $file->guessExtension();
        $file->move($uploadDir, $fileName);
        $this->setFile($uploadDir . '/' . $fileName);
    }

    /**
     * @param $message
     * @return $this
     */
    public function setStatusError($message) {
        $this->setStatus(self::STATUS_ERROR);
        $this->setMessage($message);
        return $this;
    }

    /**
     * @return string
     */
    public function __toString() {
        return (string)$this->message;
    }
}
