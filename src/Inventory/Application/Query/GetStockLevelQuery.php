<?php

declare(strict_types=1);

namespace App\Inventory\Application\Query;

use Symfony\Component\Uid\UuidV7;

final readonly class GetStockLevelQuery
{
    public function __construct(
        public UuidV7 $stockId
    ) {
    }
}
