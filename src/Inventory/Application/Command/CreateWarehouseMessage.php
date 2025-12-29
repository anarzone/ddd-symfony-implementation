<?php

namespace App\Inventory\Application\Command;

use App\Inventory\Domain\Model\Warehouse\WarehouseTypeEnum;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateWarehouseMessage
{
    public function __construct(
        #[Assert\Length(min: 3, max: 100)]
        public string $name,

        public int $capacity,

        #[Assert\Length(max: 100)]
        public string $address,

        #[Assert\Length(max: 50)]
        public string $city,

        #[Assert\Length(max: 10)]
        public string $postalCode,

        public ?float $latitude = null,

        public ?float $longitude = null,

        #[Assert\Choice(callback: [WarehouseTypeEnum::class, 'cases'])]
        public WarehouseTypeEnum $type = WarehouseTypeEnum::Standard
    ) {}
}
