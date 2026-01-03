<?php

declare(strict_types=1);

namespace App\Inventory\Application\Dto;

readonly class WarehouseInfoDto
{
    public function __construct(
        public string $id,
        public string $name,
        public int $capacity,
        public int $currentStock,
        public bool $isActive,
        public string $type,
        public string $address,
        public string $city,
        public string $postalCode,
        public ?float $latitude,
        public ?float $longitude
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'capacity' => $this->capacity,
            'currentStock' => $this->currentStock,
            'isActive' => $this->isActive,
            'type' => $this->type,
            'location' => [
                'address' => $this->address,
                'city' => $this->city,
                'postalCode' => $this->postalCode,
                'coordinates' => $this->latitude && $this->longitude
                    ? ['lat' => $this->latitude, 'lng' => $this->longitude]
                    : null,
            ],
        ];
    }
}
