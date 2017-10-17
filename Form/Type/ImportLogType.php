<?php

namespace Doctrs\SonataImportBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ImportLogType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('status')
            ->add('message')
            ->add('line')
            ->add('uploadFile')
        ;
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Doctrs\SonataImportBundle\Entity\ImportLog'
        ));
    }

    /**
     * @return string
     */
    public function getBlockPrefix() {
        return $this->getName();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'doctrs_sonataadminbundle_importlog';
    }
}
