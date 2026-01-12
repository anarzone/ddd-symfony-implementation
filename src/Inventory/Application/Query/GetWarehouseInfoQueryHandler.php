<?php

declare(strict_types=1);

namespace App\Inventory\Application\Query;

use App\Inventory\Application\Dto\WarehouseInfoDto;
use App\Inventory\Domain\Repository\WarehouseRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class GetWarehouseInfoQueryHandler
{
    public function __construct(
        private WarehouseRepositoryInterface $warehouseRepository
    ) {
    }

    public function __invoke(GetWarehouseInfoQuery $query): WarehouseInfoDto
    {
        $warehouse = $this->warehouseRepository->find($query->warehouseId->toRfc4122());

        if (!$warehouse || $warehouse->uuid === null) {
            throw new \InvalidArgumentException('Warehouse not found');
        }

        return new WarehouseInfoDto(
            id: $warehouse->uuid->toRfc4122(),
            name: $warehouse->name,
            capacity: $warehouse->capacity,
            currentStock: 0,
            isActive: $warehouse->isOpen(),
            type: $warehouse->type->name,
            address: $warehouse->location->address,
            city: $warehouse->location->city,
            postalCode: $warehouse->location->postalCode,
            latitude: $warehouse->location->latitude !== null ? (float) $warehouse->location->latitude : null,
            longitude: $warehouse->location->longitude !== null ? (float) $warehouse->location->longitude : null
        );
    }
}
