<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\DataFixtures;

use App\Inventory\Domain\Model\Stock\SKU;
use App\Inventory\Domain\Model\Stock\Stock;
use App\Inventory\Domain\Model\Warehouse\Warehouse;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class StockFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $categories = ['ELEC', 'FURN', 'CLOT', 'TOYS', 'AUTO', 'BOOK', 'SPOR', 'GARD', 'HEAL', 'FOOD'];
        $products = [
            'Smartphone', 'Laptop', 'Desk Chair', 'T-Shirt', 'Action Figure', 'Car Battery',
            'Novel', 'Basketball', 'Lawnmower', 'Vitamins', 'Coffee Maker', 'Headphones',
            'Table', 'Jeans', 'Board Game', 'Oil Filter', 'Textbook', 'Tennis Racket',
            'Rake', 'Protein Powder', 'Blender', 'Monitor', 'Bookshelf', 'Sweater',
            'Puzzle', 'Wiper Blades', 'Journal', 'Soccer Ball', 'Shovel', 'Supplements',
            'Toaster', 'Keyboard', 'Bed Frame', 'Jacket', 'Doll', 'Tire', 'Magazine',
            'Baseball Glove', 'Hose', 'First Aid Kit', 'Microwave', 'Mouse', 'Dresser',
            'Scarf', 'RC Car', 'Spark Plugs', 'Comic Book', 'Golf Clubs', 'Fertilizer',
            'Bandages', 'Air Fryer', 'Webcam', 'Nightstand', 'Polo', 'Drone', 'Brake Pads',
        ];

        $stockIndex = 0;

        $skuIndex = 0;

        // Create 200 stock items per warehouse (1000 total)
        for ($warehouseNum = 1; $warehouseNum <= 5; ++$warehouseNum) {
            $warehouse = $this->getReference('warehouse-'.$warehouseNum, Warehouse::class);

            for ($i = 0; $i < 200; ++$i) {
                $category = $categories[$skuIndex % \count($categories)];
                $product = $products[$skuIndex % \count($products)];
                $sequence = floor($skuIndex / \count($products)) + 1;

                $skuCode = \sprintf('%s-%s-%04d', $category, strtoupper(substr($product, 0, 5)), $sequence);
                $skuName = \sprintf('%s %s', $product, $sequence);

                $sku = new SKU($skuCode, $skuName);

                // Random quantity between 10 and 500
                $quantity = rand(10, 500);

                $stock = new Stock($warehouse, $sku, $quantity);
                $manager->persist($stock);

                ++$skuIndex;
                ++$stockIndex;

                // Flush every 100 items to avoid memory issues
                if ($stockIndex % 100 === 0) {
                    $manager->flush();
                    $manager->clear();
                    // Re-fetch warehouse reference after clearing
                    $warehouse = $this->getReference('warehouse-'.$warehouseNum, Warehouse::class);
                }
            }
        }

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['all'];
    }

    public function getDependencies(): array
    {
        return [
            WarehouseFixtures::class,
        ];
    }
}
