<?php

declare(strict_types=1);

namespace App\Inventory\Application\Query;

use App\Inventory\Application\Dto\WarehouseInfoDto;
use App\Inventory\Application\Dto\WarehousesListDto;
use App\Inventory\Domain\Repository\WarehouseRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class ListWarehousesQueryHandler
{
    public function __construct(
        private WarehouseRepositoryInterface $warehouseRepository
    ) {
    }

    public function __invoke(ListWarehousesQuery $query): WarehousesListDto
    {
        $warehouses = $this->warehouseRepository->findAll();

        $warehouseDtos = array_map(fn ($w) => new WarehouseInfoDto(
            id: $w->uuid !== null ? $w->uuid->toRfc4122() : '',
            name: $w->name,
            capacity: $w->capacity,
            currentStock: 0,
            isActive: $w->isOpen(),
            type: $w->type->name,
            address: $w->location->address,
            city: $w->location->city,
            postalCode: $w->location->postalCode,
            latitude: $w->location->latitude !== null ? (float) $w->location->latitude : null,
            longitude: $w->location->longitude !== null ? (float) $w->location->longitude : null
        ), $warehouses);

        return new WarehousesListDto($warehouseDtos);
    }
}
