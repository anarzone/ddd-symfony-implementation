<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command;

use App\Inventory\Domain\Model\Warehouse\Location;
use App\Inventory\Domain\Model\Warehouse\WarehouseTypeEnum;
use Symfony\Component\Uid\UuidV7;

final readonly class CreateWarehouseMessage
{
    public function __construct(
        public UuidV7 $uuid,
        public string $name,
        public int $capacity,
        public Location $location,
        public WarehouseTypeEnum $type = WarehouseTypeEnum::Standard
    ) {
    }
}
