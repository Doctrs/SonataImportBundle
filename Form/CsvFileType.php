<?php

namespace Doctrs\SonataImportBundle\Form;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class CsvFileType extends AbstractType implements ContainerAwareInterface
{
    /** @var Container $container */
    private $container;

    public function setContainer(ContainerInterface $container = null) {
        $this->container = $container;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('file', FileType::class, [
                'label' => 'File'
            ])
            ->add('submit', SubmitType::class)
        ;

        $encode =
            isset($this->container->getParameter('doctrs_sonata_import')['encode']) ?
                $this->container->getParameter('doctrs_sonata_import')['encode'] :
                null;
        $default_encode = isset($encode['default']) ? $encode['default'] : 'utf8';
        $encode_list = isset($encode['list']) ? $encode['list'] : [];
        if(!count($encode_list)){
            $builder->add('encode', HiddenType::class, [
                'data' => $default_encode
            ]);
        } else {
            $el = [];
            foreach($encode_list as $item){
                $el[$item] = $item;
            }
            $builder->add('encode', ChoiceType::class, [
                'choices' => $el,
                'data' => $default_encode
            ]);
        }
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Doctrs\SonataImportBundle\Entity\CsvFile'
        ));
    }

    /**
     * @return string
     */
    public function getBlockPrefix(){
        return $this->getName();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'promoatlas_sonataadminbundle_csvfile';
    }
}
