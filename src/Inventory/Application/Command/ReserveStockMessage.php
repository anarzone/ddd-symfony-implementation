<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command;

use Symfony\Component\Uid\UuidV7;

final readonly class ReserveStockMessage
{
    public function __construct(
        public UuidV7 $stockId,
        public int $quantity,
        public UuidV7 $userId,
        public int $minutesValid = 15,
    ) {
    }
}
