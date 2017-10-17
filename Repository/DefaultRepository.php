<?php


namespace Doctrs\SonataImportBundle\Repository;


use Doctrine\ORM\EntityRepository;
use Symfony\Component\HttpFoundation\Request;

class DefaultRepository extends EntityRepository {

    public function pagerfanta(Request $request) {
        $sql = $this->createQueryBuilder('data');
        $sql->select('data');
        switch ($request->get('type', 'all')) {
            case 'success':
                $sql->where('data.status = 1 or data.status = 2');
                break;
            case 'new':
                $sql->where('data.status = 1');
                break;
            case 'update':
                $sql->where('data.status = 2');
                break;
            case 'error':
                $sql->where('data.status = 3');
                break;
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
