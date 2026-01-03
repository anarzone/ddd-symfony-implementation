<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Repository;

use App\Inventory\Domain\Model\Warehouse\Warehouse;

/**
 * @method Warehouse|null find(string $id)
 * @method Warehouse[]    findAll()
 */
interface WarehouseRepositoryInterface
{
    public function findWithLock(string $id): ?Warehouse;

    public function save(Warehouse $warehouse): void;
}
