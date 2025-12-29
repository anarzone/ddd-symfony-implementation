<?php

namespace App\Inventory\Application\Query;

final readonly class GetStockLevelQuery
{
    public function __construct(
        public int $stockId
    ) {}
}
