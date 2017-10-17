<?php


namespace Doctrs\SonataImportBundle\Service\SonataImportType;

class BooleanType implements ImportInterface {

    public function getFormatValue($value) {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

}
