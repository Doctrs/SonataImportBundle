<?php

namespace Doctrs\SonataImportBundle\Controller;

use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Doctrs\SonataImportBundle\Entity\CsvFile;
use Doctrs\SonataImportBundle\Form\Type\CsvFileType;
use Sonata\AdminBundle\Controller\CRUDController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Process\Process;

class DefaultController extends CRUDController {

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request) {
        $fileEntity = new CsvFile();
        $form = $this->createForm(CsvFileType::class, $fileEntity, [
            'method' => 'POST'
        ]);
        $form->handleRequest($request);

        $builder = $this->get('sonata.admin.pool')->getInstance($this->admin->getCode())->getExportFields();

        if($form->isValid()){
            if(!$fileEntity->getFile()->getError()) {
                $upload_dir = $this->getParameter('doctrs_sonata_import.upload_dir');

                $file = $fileEntity->getFile();
                $fileName = md5(uniqid() . time()) . '.' . $file->guessExtension();
                $file->move($upload_dir, $fileName);
                $fileEntity->setFile($upload_dir . '/' . $fileName);

                $this->getDoctrine()->getManager()->persist($fileEntity);
                $this->getDoctrine()->getManager()->flush($fileEntity);

                $command = sprintf(
                    '/usr/bin/php %s/console doctrs:sonata:import %d "%s" "%s" %d > /dev/null 2>&1 &',
                    $this->get('kernel')->getRootDir(),
                    $fileEntity->getId(),
                    $this->admin->getCode(),
                    $fileEntity->getEncode() ? $fileEntity->getEncode() : 'utf8',
                    $fileEntity->getLoaderClass()
                );

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
            'letters' => $this->getLetterArray()
        ]);
    }

    /**
     * @param Request $request
     * @param CsvFile $csvFile
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function uploadAction(Request $request, CsvFile $csvFile){
        $em = $this->getDoctrine()->getManager();

        $countImport = $em->getRepository('DoctrsSonataImportBundle:ImportLog')->count([
            'csvFile' => $csvFile->getId()
        ]);

        $data = $em->getRepository('DoctrsSonataImportBundle:ImportLog')->pagerfanta($request);
        $paginator = new Pagerfanta(new DoctrineORMAdapter($data));
        $paginator->setCurrentPage($request->get('page', 1));

        return $this->render('@DoctrsSonataImport/Default/upload.html.twig', [
            'csvFile' => $csvFile,
            'paginator' => $paginator,
            'action' => 'upload',
            'admin' => $this->admin,
            'countImport' => $countImport,
            'baseTemplate' => $this->getBaseTemplate(),
        ]);
    }


    /**
     * @param CsvFile $csvFile
     * @return JsonResponse
     */
    public function importStatusAction(CsvFile $csvFile){
        $countImport = $this->getDoctrine()->getManager()->getRepository('DoctrsSonataImportBundle:ImportLog')->count([
            'csvFile' => $csvFile->getId()
        ]);

        return new JsonResponse([
            'status' => $csvFile->getStatus(),
            'error' => $csvFile->getMessage(),
            'count' => $countImport
        ]);
    }

    /**
     * get array from A to ZZ
     * @return array
     */
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
