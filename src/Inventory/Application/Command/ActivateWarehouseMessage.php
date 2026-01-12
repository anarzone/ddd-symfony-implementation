<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command;

use Symfony\Component\Uid\UuidV7;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class ActivateWarehouseMessage
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid]
        public UuidV7 $warehouseId
    ) {
    }
}
