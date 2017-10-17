<?php

namespace Doctrs\SonataImportBundle\Service\SonataImportType;

use Symfony\Component\Form\FormBuilderInterface;

interface FormBuilderAwareInterface{
    public function setFormBuilder(FormBuilderInterface $formBuilder);
}
