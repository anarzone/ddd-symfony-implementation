<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Inventory\Domain\Model\Warehouse\Location;
use App\Inventory\Domain\Model\Warehouse\Warehouse;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

use function Zenstruck\Foundry\faker;

final class WarehouseFactory extends PersistentObjectFactory
{
    protected function defaults(): array|callable
    {
        return [
            'name' => faker()->company(),
            'capacity' => faker()->numberBetween(100, 10000),
            'location' => new Location(
                faker()->streetAddress(),
                faker()->city(),
                faker()->postcode(),
                faker()->optional()->latitude(),
                faker()->optional()->longitude()
            ),
        ];
    }

    public static function class(): string
    {
        return Warehouse::class;
    }
}
