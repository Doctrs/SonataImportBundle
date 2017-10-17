<?php

namespace Doctrs\SonataImportBundle\Admin;


use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Route\RouteCollection;
use Symfony\Component\Security\Core\Exception\InvalidArgumentException;

trait AdminImportTrait{


    protected function configureRoutes(RouteCollection $collection)
    {
        $collection->add('import', 'import', [
            '_controller' => 'DoctrsSonataImportBundle:Default:index'
        ]);
        $collection->add('upload', '{id}/upload', [
            '_controller' => 'DoctrsSonataImportBundle:Default:upload'
        ]);
        $collection->add('importStatus', '{id}/upload/status', [
            '_controller' => 'DoctrsSonataImportBundle:Default:importStatus'
        ]);
    }


    public function getDashboardActions()
    {
        if(!$this instanceof AbstractAdmin){
            throw new InvalidArgumentException(sprintf('Class "%s" must by instanceof "Sonata\AdminBundle\Admin\AbstractAdmin"',
                get_class($this)
            ));
        }
        $actions = parent::getDashboardActions();

        $actions['import'] = array(
            'label'              => 'Import',
            'url'                => $this->generateUrl('import'),
            'icon'               => 'upload',
            'translation_domain' => 'SonataAdminBundle', // optional
            'template'           => 'SonataAdminBundle:CRUD:dashboard__action.html.twig', // optional
        );

        return $actions;
    }

}
