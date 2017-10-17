<?php


namespace Doctrs\SonataImportBundle\Service\SonataImportType;

class IntegerType implements ImportInterface{

    public function getFormatValue($value){
        return (int)$value;
    }

}
