<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\DataFixtures;

use App\Inventory\Domain\Model\Warehouse\Location;
use App\Inventory\Domain\Model\Warehouse\Warehouse;
use App\Inventory\Domain\Model\Warehouse\WarehouseTypeEnum;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Uid\UuidV7;

class WarehouseFixtures extends Fixture implements FixtureGroupInterface
{
    public function load(ObjectManager $manager): void
    {
        $warehouses = [
            [
                'name' => 'New York Distribution Center',
                'capacity' => 10000,
                'type' => WarehouseTypeEnum::Fulfillment,
                'location' => new Location('123 Logistics Way', 'New York', '10001', 40.71280000, -74.00600000),
            ],
            [
                'name' => 'Los Angeles Hub',
                'capacity' => 15000,
                'type' => WarehouseTypeEnum::Standard,
                'location' => new Location('456 Commerce Blvd', 'Los Angeles', '90001', 34.05220000, -118.24370000),
            ],
            [
                'name' => 'Chicago Cold Storage',
                'capacity' => 8000,
                'type' => WarehouseTypeEnum::Cold_Storage,
                'location' => new Location('789 Refrigeration Ave', 'Chicago', '60601', 41.87810000, -87.62980000),
            ],
            [
                'name' => 'Houston Automated Facility',
                'capacity' => 12000,
                'type' => WarehouseTypeEnum::Automated,
                'location' => new Location('321 Robotics Lane', 'Houston', '77001', 29.76040000, -95.36980000),
            ],
            [
                'name' => 'Miami Hazardous Materials',
                'capacity' => 5000,
                'type' => WarehouseTypeEnum::Hazardous,
                'location' => new Location('654 Safety Road', 'Miami', '33101', 25.76170000, -80.19180000),
            ],
            [
                'name' => 'Seattle Pacific Northwest Hub',
                'capacity' => 9000,
                'type' => WarehouseTypeEnum::Fulfillment,
                'location' => new Location('987 Emerald Street', 'Seattle', '98101', 47.60620000, -122.33210000),
            ],
            [
                'name' => 'Denver Mountain Regional Center',
                'capacity' => 7500,
                'type' => WarehouseTypeEnum::Standard,
                'location' => new Location('456 Summit Road', 'Denver', '80201', 39.73920000, -104.99030000),
            ],
            [
                'name' => 'Boston Northeast Distribution',
                'capacity' => 8500,
                'type' => WarehouseTypeEnum::Fulfillment,
                'location' => new Location('234 Harbor View', 'Boston', '02101', 42.36010000, -71.05890000),
            ],
            [
                'name' => 'Atlanta Southeast Gateway',
                'capacity' => 11000,
                'type' => WarehouseTypeEnum::Standard,
                'location' => new Location('567 Peachtree Street', 'Atlanta', '30301', 33.74900000, -84.38800000),
            ],
            [
                'name' => 'Dallas Fort Worth Logistics',
                'capacity' => 13000,
                'type' => WarehouseTypeEnum::Automated,
                'location' => new Location('890 Rodeo Drive', 'Dallas', '75201', 32.77670000, -96.79700000),
            ],
            [
                'name' => 'Phoenix Desert Storage Facility',
                'capacity' => 9500,
                'type' => WarehouseTypeEnum::Standard,
                'location' => new Location('345 Cactus Lane', 'Phoenix', '85001', 33.44840000, -112.07400000),
            ],
            [
                'name' => 'Philadelphia Mid-Atlantic Hub',
                'capacity' => 10500,
                'type' => WarehouseTypeEnum::Fulfillment,
                'location' => new Location('678 Independence Mall', 'Philadelphia', '19101', 39.95260000, -75.16520000),
            ],
            [
                'name' => 'San Francisco Bay Area Center',
                'capacity' => 12000,
                'type' => WarehouseTypeEnum::Automated,
                'location' => new Location('123 Golden Gate Blvd', 'San Francisco', '94102', 37.77490000, -122.41940000),
            ],
            [
                'name' => 'Minneapolis Midwest Distribution',
                'capacity' => 8000,
                'type' => WarehouseTypeEnum::Standard,
                'location' => new Location('456 Mall of America Way', 'Minneapolis', '55401', 44.97780000, -93.26500000),
            ],
            [
                'name' => 'Detroit Great Lakes Facility',
                'capacity' => 7000,
                'type' => WarehouseTypeEnum::Cold_Storage,
                'location' => new Location('789 Motor City Drive', 'Detroit', '48201', 42.33140000, -83.04580000),
            ],
            [
                'name' => 'San Diego Border Warehouse',
                'capacity' => 9000,
                'type' => WarehouseTypeEnum::Standard,
                'location' => new Location('234 Pacific Coast Hwy', 'San Diego', '92101', 32.71570000, -117.16110000),
            ],
            [
                'name' => 'Portland Northwest Storage',
                'capacity' => 6500,
                'type' => WarehouseTypeEnum::Cold_Storage,
                'location' => new Location('567 Rose Garden Way', 'Portland', '97201', 45.51520000, -122.67840000),
            ],
            [
                'name' => 'Orlando Florida Distribution',
                'capacity' => 8500,
                'type' => WarehouseTypeEnum::Fulfillment,
                'location' => new Location('890 Disney Springs Road', 'Orlando', '32801', 28.53830000, -81.37920000),
            ],
            [
                'name' => 'Sacramento Valley Logistics',
                'capacity' => 7500,
                'type' => WarehouseTypeEnum::Standard,
                'location' => new Location('123 Capital Mall', 'Sacramento', '95814', 38.58160000, -121.49440000),
            ],
            [
                'name' => 'Las Vegas Desert Hub',
                'capacity' => 6000,
                'type' => WarehouseTypeEnum::Standard,
                'location' => new Location('456 Las Vegas Blvd', 'Las Vegas', '89101', 36.16990000, -115.13980000),
            ],
        ];

        foreach ($warehouses as $index => $data) {
            $uuid = new UuidV7();

            $warehouse = new Warehouse(
                uuid: $uuid,
                name: $data['name'],
                capacity: $data['capacity'],
                location: $data['location']
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
