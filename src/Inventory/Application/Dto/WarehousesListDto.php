<?php

declare(strict_types=1);

namespace App\Inventory\Application\Dto;

class WarehousesListDto
{
    /**
     * @param array<WarehouseInfoDto> $warehouseDtos
     */
    public function __construct(
        public array $warehouseDtos,
    ) {
    }

    public function toArray(): array
    {
        return [
            'warehouses' => array_map(fn ($dto) => $dto->toArray(), $this->warehouseDtos),
            'count' => \count($this->warehouseDtos),
        ];
    }
}
