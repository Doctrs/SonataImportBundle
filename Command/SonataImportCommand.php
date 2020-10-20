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
use \ReflectionClass;

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
            ->addArgument('table_key', InputArgument::OPTIONAL, 'Key by which system will try to find existing entity for update')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {

        $this->em = $this->getContainer()->get('doctrine')->getManager();
        $uploadFileId = $input->getArgument('csv_file');
        $adminCode = $input->getArgument('admin_code');
        $encode = strtolower($input->getArgument('encode'));
        $fileLoaderId = $input->getArgument('file_loader');
        $tableKey = $input->getArgument('table_key');

        /** @var UploadFile $uploadFile */
        $uploadFile = $this->em->getRepository('DoctrsSonataImportBundle:UploadFile')->find($uploadFileId);

        // We need to remove utf8 BOM mark
        $content = file_get_contents($uploadFile->getFile());
        $bom = pack('H*','EFBBBF');
        $content = preg_replace("/^$bom/", '', $content);
        file_put_contents($uploadFile->getFile(), $content);

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
            $identifier = $tableKey;
            $exportFields = $instance->getExportFields();
            $form = $instance->getFormBuilder();
            $this->removeListenersBeforeUpdating(
                $entityClass
            );
            foreach ($fileLoader->getIteration() as $line => $data) {
                if ($line == 0) {
                    $exportFields = $data;
                    continue;
                }
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
                    * If the ID is specified (the first column)
                    * Looking for an entity in the database
                    */
                    if ($name == $identifier) {
                        if ($value) {
                            //$oldEntity = $instance->getObject($value);
                            $oldEntity = $this->em->getRepository(
                                get_class($entity)
                            )
                            ->findOneBy(
                                array(
                                    $identifier => $value
                                )
                            );

                            if ($oldEntity) {
                                $entity = $oldEntity;
                            } else {
                                // We're going to need that later, because it's a new entity.
                                $identifierValue = $value;
                            }
                        }
                        continue;
                    }
                    /**
                    * Fields of forms do not always correspond to what is on the site, and that in the admin area
                    * Therefore, if the field is not specified in the admin panel, then simply skip it
                    */
                    if (!$form->has($name)) {
                        continue;
                    }
                    $formBuilder = $form->get($name);
                    /**
                    * Many make errors in the standard encoding,
                    * therefore, just in case, we check both variants of writing
                    */
                    if ($encode !== 'utf8' && $encode !== 'utf-8') {
                        $value = iconv($encode, 'utf8//TRANSLIT', $value);
                    }
                    try {
                        $method = $this->getSetMethod($name);
                        $entity->$method($this->setValue($value, $formBuilder, $instance));
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
                    * If the entity does not have an ID, then it is new - add it
                    */
                    if (!$entity->$idMethod()) {
                        $idSetMethod = $this->getSetMethod($identifier, 'set');
                        if (!isset($identifierValue)) {
                            continue;
                        }
                        $entity->$idSetMethod($identifierValue);
                        $this->em->persist($entity);
                        $log->setStatus(ImportLog::STATUS_SUCCESS);
                    } else {
                        $log->setStatus(ImportLog::STATUS_EXISTS);
                    }
                    $this->em->flush($entity);
                    $log->setForeignId($entity->$idMethod());
                    $trueIdentifierMethodName = 'get'.ucfirst($meta->getSingleIdentifierFieldName());
                    $log->setForeignEntityId($entity->$trueIdentifierMethodName());
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
            * This hack is needed in case of throwing ORMException
            * If ORMException is thrown, entity manager stops
            * and you need to restart it
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
        $type = $formBuilder->getType();

        /**
        * Check the custom form types for the presence in the config.
         * In case of a match, we get the value from the class specified in the config
        */
        foreach ($mappings as $item) {
            if ($item['name'] === $type->getName()) {
                if ($this->getContainer()->has($item['class']) && $this->getContainer()->get($item['class']) instanceof ImportInterface) {
                    /** @var ImportInterface $class */

                    $class = $this->getContainer()->get($item['class']);

                    if ($class instanceof AdminAbstractAwareInterface) {
                        $class->setAdminAbstract($admin);
                    }
                    if ($class instanceof FormBuilderAwareInterface) {
                        $class->setFormBuilder($formBuilder);
                    }

                    return $class->getFormatValue($value);
                }
            }
        }

        return (string)$value;
    }

    protected function removeListenersBeforeUpdating($entityClass)
    {
        // Remove listeners for that entity, we just want to update flat table
        $listenerInst = null;
        foreach ($this->em->getEventManager()->getListeners() as $event => $listeners) {
            foreach ($listeners as $hash => $listener) {
                //echo get_class($listener).'<br/>';

                $className = new \ReflectionClass($entityClass);
                $className = $className->getShortName();
                $listenerName = new \ReflectionClass($listener);
                $listenerName = $listenerName->getShortName();
                $eventListener = $className.'EventListener';
                if ($listenerName == $eventListener) {
                    $listenerInst = $listener;
                    break 2;
                }
            }
        }
        if (!is_null($listenerInst)) {
            $evm = $this->em->getEventManager();
            $evm->removeEventListener(array('prePersist'), $listenerInst);
        }
    }
}
