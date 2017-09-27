<?php


namespace Doctrs\SonataImportBundle\Repository;


use Doctrine\ORM\EntityRepository;

class DefaultRepository extends EntityRepository{

    public function pagerfanta(array $where = [], $getResult = false){
        $sql = $this->createQueryBuilder('data');
        $sql->select('data');
        if (sizeof($where)) {
            foreach ($where as $key => $value) {
                if(is_array($value)){
                    if(!isset($value['dql'])){
                        continue;
                    }
                    if(isset($value['bindParam']) && !is_array($value['bindParam'])){
                        continue;
                    }
                    $sql->andWhere($value['dql']);
                    if(isset($value['bindParam'])) {
                        foreach ($value['bindParam'] as $key2 => $value2) {
                            $sql->setParameter($key2, $value2);
                        }
                    }
                } else {
                    $sql->andWhere('data.' . $key . ' = :' . $key);
                    $sql->setParameter($key, $value);
                }
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