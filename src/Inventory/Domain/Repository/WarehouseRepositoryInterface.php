<?php

namespace App\Inventory\Domain\Repository;

use App\Inventory\Domain\Model\Warehouse\Warehouse;

interface WarehouseRepositoryInterface
{
    public function findWithLock(int $id);

    public function save(Warehouse $warehouse);
}
