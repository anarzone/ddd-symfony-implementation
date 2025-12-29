<?php

namespace App\Inventory\Application\Command;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ReserveStockMessage
{
    public function __construct(
        public int $stockId,
        public int $quantity,
        public int $userId,

        #[Assert\Range(notInRangeMessage: 'Reservation must be between 1 and 60 minutes', min: 1, max: 60)]
        public int $minutesValid = 15
    ) {}
}
