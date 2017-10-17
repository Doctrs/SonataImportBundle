<?php

namespace Doctrs\SonataImportBundle\Command;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;
use Doctrs\SonataImportBundle\Entity\UploadFile;
use Doctrs\SonataImportBundle\Entity\ImportLog;
use Doctrs\SonataImportBundle\Loaders\CsvFileLoader;
use Doctrs\SonataImportBundle\Loaders\FileLoaderInterface;
use Doctrs\SonataImportBundle\Service\SonataImportType\AdminAbstractAwareInterface;
use Doctrs\SonataImportBundle\Service\SonataImportType\FormBuilderAwareInterface;
use Doctrs\SonataImportBundle\Service\SonataImportType\ImportInterface;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\File\File;

class SonataImportCommand extends ContainerAwareCommand {

    /** @var EntityManager $this->em  */
    protected $em;

    protected function configure() {
        $this
            ->setName('doctrs:sonata:import')
            ->setDescription('Import data to sonata from CSV')
            ->addArgument('csv_file', InputArgument::REQUIRED, 'id UploadFile entity')
            ->addArgument('admin_code', InputArgument::REQUIRED, 'code to sonata admin bundle')
            ->addArgument('encode', InputArgument::OPTIONAL, 'file encode')
            ->addArgument('file_loader', InputArgument::OPTIONAL, 'number of loader class')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {

        $this->em = $this->getContainer()->get('doctrine')->getManager();
        $uploadFileId = $input->getArgument('csv_file');
        $adminCode = $input->getArgument('admin_code');
        $encode = strtolower($input->getArgument('encode'));
        $fileLoaderId = $input->getArgument('file_loader');

        /** @var UploadFile $uploadFile */
        $uploadFile = $this->em->getRepository('DoctrsSonataImportBundle:UploadFile')->find($uploadFileId);
        $fileLoaders = $this->getContainer()->getParameter('doctrs_sonata_import.class_loaders');
        $fileLoader = isset($fileLoaders[$fileLoaderId], $fileLoaders[$fileLoaderId]['class']) ?
            $fileLoaders[$fileLoaderId]['class'] : null;

        if (!class_exists($fileLoader)) {
            $uploadFile->setStatusError('class_loader not found');
            $this->em->flush($uploadFile);
            return;
        }
        $fileLoader = new $fileLoader();
        if (!$fileLoader instanceof FileLoaderInterface) {
            $uploadFile->setStatusError('class_loader must be instanceof "FileLoaderInterface"');
            $this->em->flush($uploadFile);
            return;
        }


        try {
            $fileLoader->setFile(new File($uploadFile->getFile()));

            $pool = $this->getContainer()->get('sonata.admin.pool');
            /** @var AbstractAdmin $instance */
            $instance = $pool->getInstance($adminCode);
            $entityClass = $instance->getClass();
            $meta = $this->em->getClassMetadata($entityClass);
            $identifier = $meta->getSingleIdentifierFieldName();
            $exportFields = $instance->getExportFields();
            $form = $instance->getFormBuilder();
            foreach ($fileLoader->getIteration() as $line => $data) {

                $log = new ImportLog();
                $log
                    ->setLine($line)
                    ->setUploadFile($uploadFile)
                ;

                $entity = new $entityClass();
                $errors = [];
                foreach ($exportFields as $key => $name) {
                    $value = isset($data[$key]) ? $data[$key] : '';

                    /**
                     * В случае если указан ID (первый столбец)
                     * ищем сущность в базе
                     */
                    if ($name === $identifier) {
                        if ($value) {
                            $oldEntity = $instance->getObject($value);
                            if ($oldEntity) {
                                $entity = $oldEntity;
                            }
                        }
                        continue;
                    }
                    /**
                     * Поля форм не всегда соответствуют тому, что есть на сайте, и что в админке
                     * Поэтому если поле не указано в админке, то просто пропускаем его
                     */
                    if (!$form->has($name)) {
                        continue;
                    }
                    $formBuilder = $form->get($name);
                    /**
                     * Многие делают ошибки в стандартной кодировке,
                     * поэтому на всякий случай провверяем оба варианта написания
                     */
                    if ($encode !== 'utf8' && $encode !== 'utf-8') {
                        $value = iconv($encode, 'utf8//TRANSLIT', $value);
                    }
                    try {
                        $method = $this->getSetMethod($name);
                        $entity->$method($this->getValue($value, $formBuilder, $instance));
                    } catch (\Exception $e) {
                        $errors[] = $e->getMessage();
                        break;
                    }

                }
                if (!count($errors)) {
                    $validator = $this->getContainer()->get('validator');
                    $errors = $validator->validate($entity);
                }

                if (!count($errors)) {
                    $idMethod = $this->getSetMethod($identifier, 'get');
                    /**
                     * Если у сещности нет ID, то она новая - добавляем ее
                     */
                    if (!$entity->$idMethod()) {
                        $this->em->persist($entity);
                        $log->setStatus(ImportLog::STATUS_SUCCESS);
                    } else {
                        $log->setStatus(ImportLog::STATUS_EXISTS);
                    }
                    $this->em->flush($entity);
                    $log->setForeignId($entity->$idMethod());
                } else {
                    $log->setMessage(json_encode($errors));
                    $log->setStatus(ImportLog::STATUS_ERROR);
                }
                $this->em->persist($log);
                $this->em->flush($log);
            }
            $uploadFile->setStatus(UploadFile::STATUS_SUCCESS);
            $this->em->flush($uploadFile);
        } catch (\Exception $e) {
            /**
             * Данный хак нужен в случае бросания ORMException
             * В случае бросания ORMException entity manager останавливается
             * и его требуется перезагрузить
             */
            if (!$this->em->isOpen()) {
                $this->em = $this->em->create(
                    $this->em->getConnection(),
                    $this->em->getConfiguration()
                );
                $uploadFile = $this->em->getRepository('DoctrsSonataImportBundle:UploadFile')->find($uploadFileId);
            }

            $uploadFile->setStatusError($e->getMessage());
            $this->em->flush($uploadFile);
        }
    }


    protected function getSetMethod($name, $method = 'set') {
        return $method . str_replace(' ', '', ucfirst(join('', explode('_', $name))));
    }

    protected function setValue($value, FormBuilderInterface $formBuilder, AbstractAdmin $admin) {

        $mappings = $this->getContainer()->getParameter('doctrs_sonata_import.mappings');
        $type = $formBuilder->getType()->getName();

        /**
         * Проверяем кастомные типы форм на наличие в конфиге.
         * В случае совпадения, получаем значение из класса, указанного в конфиге
         */
        foreach ($mappings as $item) {
            if ($item['name'] === $type) {
                if ($this->getContainer()->has($item['class']) && $this->getContainer()->get($item['class']) instanceof ImportInterface) {
                    /** @var ImportInterface $class */

                    $class = $this->getContainer()->get($item['class']);

                    if($class instanceof AdminAbstractAwareInterface){
                        $class->setAdminAbstract($admin);
                    }
                    if($class instanceof FormBuilderAwareInterface){
                        $class->setFormBuilder($formBuilder);
                    }

                    return $class->getFormatValue($value);
                }
            }
        }

        return (string)$value;
    }
}
