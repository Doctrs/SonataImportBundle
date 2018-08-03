<?php

namespace Doctrs\SonataImportBundle\Form\Type;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class UploadFileType extends AbstractType
{
    use ContainerAwareTrait;

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('file', FileType::class, [
                'label' => 'form.file'
            ])
        ;

        $default_encode = $this->container->getParameter('doctrs_sonata_import.encode.default');
        $encode_list = $this->container->getParameter('doctrs_sonata_import.encode.list');
        if (!count($encode_list)) {
            $builder->add('encode', HiddenType::class, [
                'data' => $default_encode,
                'label' => 'form.encode'
            ]);
        } else {
            $el = [];
            foreach ($encode_list as $item) {
                $el[$item] = $item;
            }
            $builder->add('encode', ChoiceType::class, [
                'choices' => $el,
                'data' => $default_encode,
                'label' => 'form.encode'
            ]);
        }

        $loader = [];
        $loaders_list = $this->container->getParameter('doctrs_sonata_import.class_loaders');
        foreach ($loaders_list as $key => $item) {
            $loader[$item['name']] = $key;
        }
        $builder->add('loaderClass', ChoiceType::class, [
            'choices' => $loader,
            'label' => 'form.loader_class'
        ]);

        $tableKeys = array(
            'sku' => 'Sku',
            'id' => 'ID'
        );
        $defaultKey = 'sku';
        $builder->add('tableKey', ChoiceType::class, [
                'choices' => $tableKeys,
                'data' => $defaultKey,
                'label' => 'form.default_key.label',
                'help_block' => 'form.default_key.help_block'
            ]
        );

        $builder
            ->add('submit', SubmitType::class, [
                'label' => 'form.submit',
                'attr' => [
                    'class' => 'btn btn-success align-right'
                ]
            ])
        ;


    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Doctrs\SonataImportBundle\Entity\UploadFile',
            'translation_domain' => 'DoctrsSonataImportBundle'
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
        return 'doctrs_sonataadminbundle_uploadfile';
    }
}
