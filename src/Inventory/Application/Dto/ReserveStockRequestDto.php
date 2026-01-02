<?php

namespace App\Inventory\Application\Dto;

use Symfony\Component\Uid\UuidV7;
use Symfony\Component\Validator\Constraints as Assert;


final class ReserveStockRequestDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid]
        public UuidV7 $stockId,

        #[Assert\NotNull]
        #[Assert\Positive]
        public int $quantity,

        #[Assert\Range(min: 1, max: 60)]
        public int    $minutesValid = 15,
    )
    {
    }
}
