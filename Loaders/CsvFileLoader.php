<?php

namespace Doctrs\SonataImportBundle\Loaders;


use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Process\Exception\InvalidArgumentException;

class CsvFileLoader implements FileLoaderInterface{

    /** @var File $file  */
    protected $file = null;

    public function setFile(File $file) : FileLoaderInterface {
        $this->file = $file;
        return $this;
    }

    public function getIteration() {
        if(!$this->file){
            throw new InvalidArgumentException('File not found');
        }

        $file = fopen($this->file->getRealPath(), 'r');
        while (($line = fgetcsv($file, 0, ',')) !== false) {
            yield $line;
        }
    }

}
