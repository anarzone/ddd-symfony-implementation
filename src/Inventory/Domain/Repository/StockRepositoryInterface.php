<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Repository;

use App\Inventory\Domain\Model\Stock\Stock;
use Symfony\Component\Uid\UuidV7;

/**
 * @method Stock|null find(UuidV7 $id)
 */
interface StockRepositoryInterface
{
    public function findWithLock(UuidV7 $id): ?Stock;

    public function findAllWithWarehouse(): ?array;

    public function save(Stock $stock): void;
}
