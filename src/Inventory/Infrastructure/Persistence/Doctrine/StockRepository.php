<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Persistence\Doctrine;

use App\Inventory\Domain\Model\Stock\Stock;
use App\Inventory\Domain\Repository\StockRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\UuidV7;

class StockRepository extends ServiceEntityRepository implements StockRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Stock::class);
    }

    public function findWithLock(UuidV7 $id): ?Stock
    {
        $entity = $this->find($id, LockMode::PESSIMISTIC_WRITE);

        return $entity instanceof Stock ? $entity : null;
    }

    public function findAllWithWarehouse(): ?array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.warehouse', 'w')
            ->addSelect('w')
            ->getQuery()
            ->getResult();
    }

    public function save(Stock $stock): void
    {
        $this->getEntityManager()->persist($stock);
        $this->getEntityManager()->flush();
    }
}
