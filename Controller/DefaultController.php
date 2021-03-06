<?php

namespace Doctrs\SonataImportBundle\Controller;

use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Doctrs\SonataImportBundle\Entity\UploadFile;
use Doctrs\SonataImportBundle\Form\Type\UploadFileType;
use Sonata\AdminBundle\Controller\CRUDController;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends CRUDController {

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request) {
        $fileEntity = new UploadFile();
        $form = $this->createForm(UploadFileType::class, $fileEntity, [
            'method' => 'POST'
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$fileEntity->getFile()->getError()) {
                $fileEntity->move($this->getParameter('doctrs_sonata_import.upload_dir'));

                $this->getDoctrine()->getManager()->persist($fileEntity);
                $this->getDoctrine()->getManager()->flush($fileEntity);

                $this->runCommand($fileEntity);
                return $this->redirect($this->admin->generateUrl('upload', [
                    'id' => $fileEntity->getId()
                ]));
            } else {
                $form->get('file')->addError(new FormError($fileEntity->getFile()->getErrorMessage()));
            }
        }


        $builder = $this->get('sonata.admin.pool')
            ->getInstance($this->admin->getCode())
            ->getExportFields()
        ;
        return $this->render('@DoctrsSonataImport/Default/index.html.twig', [
            'form' => $form->createView(),
            'baseTemplate' => $this->getBaseTemplate(),
            'builder' => $builder,
            'action' => 'import',
            'letters' => $this->getLetterArray()
        ]);
    }

    /**
     * @param Request    $request
     * @param string $id
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function uploadAction(Request $request, $id) {
        $em = $this->getDoctrine()->getManager();

        $uploadFile = $em->getRepository('DoctrsSonataImportBundle:UploadFile')->find($id);

        $countImport = $em->getRepository('DoctrsSonataImportBundle:ImportLog')->count([
            'uploadFile' => $uploadFile->getId()
        ]);

        $data = $em->getRepository('DoctrsSonataImportBundle:ImportLog')->pagerfanta($request);
        $paginator = new Pagerfanta(new DoctrineORMAdapter($data));
        $paginator->setCurrentPage($request->get('page', 1));

        return $this->render('@DoctrsSonataImport/Default/upload.html.twig', [
            'uploadFile' => $uploadFile,
            'paginator' => $paginator,
            'action' => 'upload',
            'admin' => $this->admin,
            'countImport' => $countImport,
            'baseTemplate' => $this->getBaseTemplate(),
        ]);
    }


    /**
     * @param string $id
     * @return JsonResponse
     */
    public function importStatusAction($id) {
        $uploadFile = $this->getDoctrine()->getManager()->getRepository('DoctrsSonataImportBundle:UploadFile')->find($id);

        $countImport = $this->getDoctrine()->getManager()->getRepository('DoctrsSonataImportBundle:ImportLog')->count([
            'uploadFile' => $uploadFile->getId()
        ]);

        return new JsonResponse([
            'status' => $uploadFile->getStatus(),
            'error' => $uploadFile->getMessage(),
            'count' => $countImport
        ]);
    }

    /**
     * get array from A to ZZ
     * @return array
     */
    private function getLetterArray() {
        $array = range('A', 'Z');
        $letters = $array;
        foreach ($array as $first) {
            foreach ($array as $second) {
                $letters[] = $first . $second;
            }
        }
        return $letters;
    }

    /**
     * @param UploadFile $fileEntity
     */
    private function runCommand(UploadFile $fileEntity) {
        $application = new Application($this->get('kernel'));
        $application->setAutoExit(false);

        $input = new ArrayInput(array(
            'command' => 'doctrs:sonata:import',
            'csv_file' => $fileEntity->getId(),
            'admin_code' => $this->admin->getCode(),
            'encode' => $fileEntity->getEncode() ? $fileEntity->getEncode() : 'utf8',
            'file_loader' => $fileEntity->getLoaderClass(),
        ));

        $output = new NullOutput();
        $application->run($input, $output);
    }
}
