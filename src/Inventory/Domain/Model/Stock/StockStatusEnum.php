<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Model\Stock;

enum StockStatusEnum: string
{
    case InStock = 'IN_STOCK';
    case OutOfStock = 'OUT_OF_STOCK';
    case Discontinued = 'DISCONTINUED';

    public function values(): array
    {
        return array_map(fn ($case) => $case->value, self::cases());
    }
}
