<?php

declare(strict_types=1);

namespace App\Inventory\Application\Dto;

use App\Inventory\Domain\Model\Warehouse\WarehouseTypeEnum;
use Symfony\Component\Validator\Constraints as Assert;

final class CreateWarehouseRequestDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 3, max: 100)]
        public string $name,
        #[Assert\NotNull]
        #[Assert\Positive]
        public int $capacity,
        #[Assert\NotBlank]
        #[Assert\Length(max: 100)]
        public string $address,
        #[Assert\NotBlank]
        #[Assert\Length(max: 50)]
        public string $city,
        #[Assert\NotBlank]
        #[Assert\Length(max: 10)]
        public string $postalCode,
        #[Assert\Range(min: -90, max: 90)]
        public ?float $latitude = null,
        #[Assert\Range(min: -180, max: 180)]
        public ?float $longitude = null,
        #[Assert\Choice(callback: [WarehouseTypeEnum::class, 'cases'])]
        public WarehouseTypeEnum $type = WarehouseTypeEnum::Standard
    ) {
    }
}
