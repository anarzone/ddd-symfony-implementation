<?php

namespace App\Inventory\Domain\Repository;

use App\Inventory\Domain\Model\Stock\Stock;
use Symfony\Component\Uid\UuidV7;

interface StockRepositoryInterface
{
    public function findWithLock(UuidV7 $id);

    public function findAllWithWarehouse(): ?array;

    public function save(Stock $stock);
}
