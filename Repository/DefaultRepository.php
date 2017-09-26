<?php


namespace Doctrs\SonataImportBundle\Repository;


use Doctrine\ORM\EntityRepository;

class DefaultRepository extends EntityRepository{

    public function pagerfanta(array $where = [], $getResult = false){
        $sql = $this->createQueryBuilder('data');
        $sql->select('data');
        if (sizeof($where)) {
            foreach ($where as $key => $value) {
                $sql->andWhere('data.' . $key . ' = :' . $key);
                $sql->setParameter($key, $value);
            }
        }
        if($getResult){
            return $sql->getQuery()->getResult();
        }
        return $sql->getQuery();
    }

    public function count(array $where = []) {
        $sql = $this->createQueryBuilder('data');
        $sql->select('COUNT(data)');
        if (sizeof($where)) {
            foreach ($where as $key => $value) {
                $sql->andWhere('data.' . $key . ' = :' . $key);
                $sql->setParameter($key, $value);
            }
        }

        return $sql->getQuery()->getSingleScalarResult();
    }

}