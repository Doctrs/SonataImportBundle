<?php


namespace Doctrs\SonataImportBundle\Service\SonataImportType;

use Doctrine\ORM\ORMException;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Form\FormBuilderInterface;

class EntityType implements ImportInterface, AdminAbstractAwareInterface, FormBuilderAwareInterface {

    /**
     * @var AbstractAdmin
     */
    private $abstractAdmin;

    /**
     * @var FormBuilderInterface
     */
    private $formBuilder;


    public function getFormatValue($value) {
        if (!$value) {
            return null;
        }
        if (!$this->formBuilder->getOption('class')) {
            return $value;
        }

        $admin = $this->abstractAdmin;
        $field = $this->formBuilder->getName();

        /** @var \Doctrine\ORM\Mapping\ClassMetadata $metaData */
        $metaData = $admin->getModelManager()
            ->getEntityManager($admin->getClass())->getClassMetadata($admin->getClass());
        $associations = $metaData->getAssociationNames();

        if (!in_array($field, $associations)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Unknown association "%s", association does not exist in entity "%s", available associations are "%s".',
                    $field,
                    $admin->getClass(),
                    implode(', ', $associations)
                )
            );
        }

        $repo = $admin->getConfigurationPool()->getContainer()->get('doctrine')->getManager()
            ->getRepository($this->formBuilder->getOption('class'));

        /**
         * Если значение число, то пытаемся найти его по ID.
         * Если значение не число, то ищем его по полю name
         */
        if (is_numeric($value)) {
            $value = $repo->find($value);
        } else {
            try {
                $value = $repo->findOneBy([
                    'name' => $value
                ]);
            } catch (ORMException $e) {
                throw new InvalidArgumentException('Field name not found');
            }
        }

        if (!$value) {
            throw new InvalidArgumentException(
                sprintf(
                    'Edit failed, object with id "%s" not found in association "%s".',
                    $value,
                    $field)
            );
        }
        return $value;
    }

    /**
     * @param AbstractAdmin $abstractAdmin
     */
    public function setAdminAbstract(AbstractAdmin $abstractAdmin) {
        $this->abstractAdmin = $abstractAdmin;
    }

    /**
     * @param FormBuilderInterface $formBuilder
     */
    public function setFormBuilder(FormBuilderInterface $formBuilder) {
        $this->formBuilder = $formBuilder;
    }

}
