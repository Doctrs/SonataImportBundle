<?php

namespace Doctrs\SonataImportBundle\Admin;


use Sonata\AdminBundle\Route\RouteCollection;

trait AdminImportTrait{


    protected function configureRoutes(RouteCollection $collection)
    {
        $collection->add('import', 'import', [
            '_controller' => 'DoctrsSonataImportBundle:Default:index'
        ]);
        $collection->add('upload', '{id}/upload', [
            '_controller' => 'DoctrsSonataImportBundle:Default:upload'
        ]);
    }


    public function getDashboardActions()
    {
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