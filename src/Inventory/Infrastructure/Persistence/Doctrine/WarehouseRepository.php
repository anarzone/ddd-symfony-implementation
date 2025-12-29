<?php

namespace App\Inventory\Infrastructure\Persistence\Doctrine;

use App\Inventory\Domain\Model\Warehouse\Warehouse;
use App\Inventory\Domain\Repository\WarehouseRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;

class WarehouseRepository extends ServiceEntityRepository implements WarehouseRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Warehouse::class);
    }

    public function findWithLock(int $id): ?Warehouse
    {
        $entity = $this->find($id, LockMode::PESSIMISTIC_WRITE);

        return $entity instanceof Warehouse ? $entity : null;
    }

    public function save(Warehouse $warehouse): void
    {
        $this->getEntityManager()->persist($warehouse);
        $this->getEntityManager()->flush();
    }
}
