<?php

namespace App\Inventory\Domain\Repository;

use App\Inventory\Domain\Model\Warehouse\Warehouse;

interface WarehouseRepositoryInterface
{
    public function findWithLock(string $id);

    public function save(Warehouse $warehouse);
}
