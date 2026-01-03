<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Model\Warehouse;

enum WarehouseTypeEnum: string
{
    case Cold_Storage = 'COLD_STORAGE';
    case Fulfillment = 'FULFILLMENT';
    case Standard = 'STANDARD';
    case Hazardous = 'HAZARDOUS';
    case Automated = 'AUTOMATED';

    public static function values(): array
    {
        return array_map(fn ($case) => $case->value, self::cases());
    }
}
