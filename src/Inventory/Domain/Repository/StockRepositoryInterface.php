<?php

namespace App\Inventory\Domain\Repository;

use App\Inventory\Domain\Model\Stock\Stock;

interface StockRepositoryInterface
{
    public function findWithLock(int $id);

    public function findAllWithWarehouse(): ?array;

    public function save(Stock $stock);
}
