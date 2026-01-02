<?php

namespace App\Tests\Factory;

use App\Inventory\Domain\Model\Stock\SKU;
use App\Inventory\Domain\Model\Stock\Stock;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use function Zenstruck\Foundry\faker;

final class StockFactory extends PersistentObjectFactory
{
    protected function defaults(): array|callable
    {
        return [
            'warehouse' => WarehouseFactory::new(),
            'sku' => new SKU(
                faker()->lexify('???-').faker()->numerify('####'),
                faker()->words(3, true)
            ),
            'totalQuantity' => faker()->numberBetween(10, 1000),
        ];
    }

    public static function class(): string
    {
        return Stock::class;
    }
}
