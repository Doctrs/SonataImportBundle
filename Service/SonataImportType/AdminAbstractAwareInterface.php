<?php

namespace Doctrs\SonataImportBundle\Service\SonataImportType;

use Sonata\AdminBundle\Admin\AbstractAdmin;

interface AdminAbstractAwareInterface {
    public function setAdminAbstract(AbstractAdmin $abstractAdmin);
}
