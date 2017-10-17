<?php


namespace Doctrs\SonataImportBundle\Service\SonataImportType;

class DateType implements ImportInterface{

    public function getFormatValue($value){
        return $value ? new \DateTime($value) : null;
    }

}
