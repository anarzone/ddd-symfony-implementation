<?php

declare(strict_types=1);

namespace App\Inventory\Application\Dto;

readonly class StockLevelDto
{
    public function __construct(
        public string $stockId,
        public string $skuCode,
        public string $skuName,
        public int $totalQuantity,
        public int $availableQuantity,
        public int $reservedQuantity,
        public string $warehouseName,
        public string $location
    ) {
    }

    public function toArray(): array
    {
        return [
            'stockId' => $this->stockId,
            'sku' => [
                'code' => $this->skuCode,
                'name' => $this->skuName,
            ],
            'quantities' => [
                'total' => $this->totalQuantity,
                'available' => $this->availableQuantity,
                'reserved' => $this->reservedQuantity,
            ],
            'warehouse' => [
                'name' => $this->warehouseName,
                'location' => $this->location,
            ],
        ];
    }
}
