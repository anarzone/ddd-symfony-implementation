<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\DataFixtures;

use App\Inventory\Domain\Model\Warehouse\Location;
use App\Inventory\Domain\Model\Warehouse\Warehouse;
use App\Inventory\Domain\Model\Warehouse\WarehouseTypeEnum;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

class WarehouseFixtures extends Fixture implements FixtureGroupInterface
{
    public function load(ObjectManager $manager): void
    {
        $warehouses = [
            [
                'name' => 'New York Distribution Center',
                'capacity' => 10000,
                'type' => WarehouseTypeEnum::Fulfillment,
                'location' => new Location('123 Logistics Way', 'New York', '10001', '40.71280000', '-74.00600000'),
            ],
            [
                'name' => 'Los Angeles Hub',
                'capacity' => 15000,
                'type' => WarehouseTypeEnum::Standard,
                'location' => new Location('456 Commerce Blvd', 'Los Angeles', '90001', '34.05220000', '-118.24370000'),
            ],
            [
                'name' => 'Chicago Cold Storage',
                'capacity' => 8000,
                'type' => WarehouseTypeEnum::Cold_Storage,
                'location' => new Location('789 Refrigeration Ave', 'Chicago', '60601', '41.87810000', '-87.62980000'),
            ],
            [
                'name' => 'Houston Automated Facility',
                'capacity' => 12000,
                'type' => WarehouseTypeEnum::Automated,
                'location' => new Location('321 Robotics Lane', 'Houston', '77001', '29.76040000', '-95.36980000'),
            ],
            [
                'name' => 'Miami Hazardous Materials',
                'capacity' => 5000,
                'type' => WarehouseTypeEnum::Hazardous,
                'location' => new Location('654 Safety Road', 'Miami', '33101', '25.76170000', '-80.19180000'),
            ],
        ];

        foreach ($warehouses as $index => $data) {
            $warehouse = new Warehouse(
                $data['name'],
                $data['capacity'],
                $data['location']
            );
            $warehouse->changeType($data['type']);

            $manager->persist($warehouse);
            $this->addReference('warehouse-'.($index + 1), $warehouse);
        }

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['all'];
    }
}
