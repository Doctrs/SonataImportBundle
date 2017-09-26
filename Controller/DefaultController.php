<?php

namespace Doctrs\SonataImportBundle\Controller;

use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Doctrs\SonataImportBundle\Entity\CsvFile;
use Doctrs\SonataImportBundle\Form\CsvFileType;
use Sonata\AdminBundle\Controller\CRUDController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Process\Process;

class DefaultController extends CRUDController {

    public function indexAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
        $fileEntity = new CsvFile();
        $form = $this->createForm(CsvFileType::class, $fileEntity, [
            'method' => 'POST'
        ]);
        $form->handleRequest($request);

        $pool = $this->container->get('sonata.admin.pool');
        /** @var AbstractAdmin $instance */
        $instance = $pool->getInstance($this->admin->getCode());
        $builder = $instance->getExportFields();

        if($form->isValid()){
            if(!$fileEntity->getFile()->getError()) {
                $upload_dir =
                    isset($this->getParameter('doctrs_sonata_import')['upload_dir']) ?
                        $this->getParameter('doctrs_sonata_import')['upload_dir'] :
                        $this->get('kernel')->getRootDir() . '/../web/uploads'
                ;

                $file = $fileEntity->getFile();
                $fileName = md5(uniqid() . time()) . '.' . $file->guessExtension();
                $file->move($upload_dir, $fileName);
                $fileEntity->setFile($upload_dir . '/' . $fileName);

                $em->persist($fileEntity);
                $em->flush($fileEntity);

                $command = '/usr/bin/php ';
                $command .= $this->container->getParameter('kernel.root_dir') . '/console ';
                $command .= 'promoatlas:sonata:import ';
                $command .= $fileEntity->getId() . ' ';
                $command .= '"' . $this->admin->getCode() . '" ';
                $command .= '"' . ($fileEntity->getEncode() ? $fileEntity->getEncode() : 'utf8') . '" ';
                $command .= '  > /dev/null 2>&1 &';

                $process = new Process($command);
                $process->run();
                return $this->redirect($this->admin->generateUrl('upload', [
                    'id' => $fileEntity->getId()
                ]));
            } else {
                $form->get('file')->addError(new FormError($fileEntity->getFile()->getErrorMessage()));
            }
        }


        return $this->render('@DoctrsSonataImport/Default/index.html.twig', [
            'form' => $form->createView(),
            'baseTemplate' => $this->getBaseTemplate(),
            'builder' => $builder,
            'action' => 'import',
            '_navbar_title' => 'sdfsdf',
            'letters' => $this->getLetterArray()
        ]);
    }

    public function uploadAction(Request $request, $id){
        $em = $this->getDoctrine()->getManager();
        $csvFile = $em->getRepository('DoctrsSonataImportBundle:CsvFile')->find($id);
        if(!$csvFile){
            return $this->redirect($this->admin->generateUrl('import'));
        }

        $countImport = $em->getRepository('DoctrsSonataImportBundle:ImportLog')->count([
            'csvFile' => $csvFile->getId()
        ]);

        if($request->get('ajax')){
            return new JsonResponse([
                'status' => $csvFile->getStatus(),
                'count' => $countImport
            ]);
        }
        $data = $em->getRepository('DoctrsSonataImportBundle:ImportLog')->pagerfanta([
            'csvFile' => $csvFile->getId()
        ]);
        $paginator = new Pagerfanta(new DoctrineORMAdapter($data));
        $paginator->setCurrentPage($request->get('page', 1));

        return $this->render('@DoctrsSonataImport/Default/upload.html.twig', [
            'csvFile' => $csvFile,
            'paginator' => $paginator,
            'action' => 'upload',
            'admin' => $this->admin,
            'countImport' => $countImport,
            'baseTemplate' => $this->getBaseTemplate(),
            'ajaxUrl' => $this->admin->generateUrl('upload', [
                'id' => $id,
                'ajax' => true
            ])
        ]);
    }

    private function getLetterArray(){
        $array = range('A', 'Z');
        $letters = $array;
        foreach($array as $first) {
            foreach ($array as $second) {
                $letters[] = $first . $second;
            }
        }
        return $letters;
    }
}
