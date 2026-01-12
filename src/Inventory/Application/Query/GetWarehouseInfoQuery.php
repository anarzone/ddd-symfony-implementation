<?php

declare(strict_types=1);

namespace App\Inventory\Application\Query;

use Symfony\Component\Uid\UuidV7;
use Symfony\Component\Validator\Constraints as Assert;

readonly class GetWarehouseInfoQuery
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid]
        public UuidV7 $warehouseId
    ) {
    }
}
