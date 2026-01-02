<?php

declare(strict_types=1);

namespace App\Tests\Integration\Inventory\Infrastructure\Persistence;

use App\Inventory\Domain\Repository\StockRepositoryInterface;
use App\Tests\Factory\StockFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\Container;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class StockRepositoryTest extends WebTestCase
{
    use ResetDatabase, Factories;

    private Container $container;
    private EntityManagerInterface $em;
    private StockRepositoryInterface $stockRepository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->container = static::getContainer();
        $this->em = $this->container->get('doctrine.orm.entity_manager');
        $this->stockRepository = $this->container->get(StockRepositoryInterface::class);
    }

    // ==================== findWithLock() Tests ====================

    public function test_findStockById_withLock_returnsStock(): void
    {
        // Given: stock with 10 units exists
        $stock = StockFactory::createOne(['totalQuantity' => 10]);

        // When: fetching stock with lock
        $this->em->wrapInTransaction(function() use ($stock, &$foundStock) {
            $foundStock = $this->stockRepository->findWithLock($stock->id);
        });

        // Then: stock is returned with correct available quantity
        $this->assertNotNull($foundStock);
        $this->assertEquals(10, $foundStock->getAvailableQuantity());
    }

    public function test_findStockById_whenNotFound_returnsNull(): void
    {
        // Given: non-existent UUID
        $nonExistentId = new \Symfony\Component\Uid\UuidV7();

        // When: fetching stock with lock
        $this->em->wrapInTransaction(function() use ($nonExistentId, &$foundStock) {
            $foundStock = $this->stockRepository->findWithLock($nonExistentId);
        });

        // Then: null is returned
        $this->assertNull($foundStock);
    }

    // ==================== findAllWithWarehouse() Tests ====================

    public function test_findAllStocks_returnsAllWithWarehouse(): void
    {
        // Given: multiple stocks exist with warehouses
        StockFactory::createMany(3, ['totalQuantity' => 50]);

        // When: fetching all stocks with warehouse
        $stocks = $this->stockRepository->findAllWithWarehouse();

        // Then: all stocks are returned with their warehouse data
        $this->assertIsArray($stocks);
        $this->assertCount(3, $stocks);
        $this->assertNotEmpty($stocks);

        foreach ($stocks as $stock) {
            $this->assertNotNull($stock->warehouse);
            $this->assertNotNull($stock->warehouse->name);
        }
    }

    public function test_findAllStocks_whenEmpty_returnsEmptyArray(): void
    {
        // Given: database is empty (ResetDatabase trait handles this)

        // When: fetching all stocks
        $stocks = $this->stockRepository->findAllWithWarehouse();

        // Then: empty array is returned
        $this->assertIsArray($stocks);
        $this->assertCount(0, $stocks);
    }

    public function test_findAllStocks_withMultipleStocks_returnsAll(): void
    {
        // Given: stocks with different quantities
        StockFactory::createOne(['totalQuantity' => 10]);
        StockFactory::createOne(['totalQuantity' => 20]);
        StockFactory::createOne(['totalQuantity' => 30]);

        // When: fetching all stocks
        $stocks = $this->stockRepository->findAllWithWarehouse();

        // Then: all stocks are returned
        $this->assertCount(3, $stocks);

        $quantities = array_map(fn($stock) => $stock->totalQuantity, $stocks);
        $this->assertContains(10, $quantities);
        $this->assertContains(20, $quantities);
        $this->assertContains(30, $quantities);
    }

    // ==================== save() Tests ====================

    public function test_save_newStock_persistsToDatabase(): void
    {
        // Given: stock created via factory (persisted by Foundry)
        $stockProxy = StockFactory::createOne(['totalQuantity' => 100]);

        // When: retrieving stock from database
        $this->em->wrapInTransaction(function() use ($stockProxy, &$foundStock) {
            $foundStock = $this->stockRepository->findWithLock($stockProxy->id);
        });

        // Then: stock is persisted correctly
        $this->assertNotNull($foundStock);
        $this->assertEquals(100, $foundStock->totalQuantity);
    }

    public function test_save_existingStock_updatesChanges(): void
    {
        // Given: existing stock via factory
        $stockProxy = StockFactory::createOne(['totalQuantity' => 50]);

        $this->em->wrapInTransaction(function() use ($stockProxy, &$stock) {
            $stock = $this->stockRepository->findWithLock($stockProxy->id);

            // When: updating and saving stock
            $stock->adjustQuantity(75);
            $this->stockRepository->save($stock);
        });

        // Then: changes are persisted
        $this->em->wrapInTransaction(function() use ($stockProxy, &$updatedStock) {
            $updatedStock = $this->stockRepository->findWithLock($stockProxy->id);
        });

        $this->assertEquals(75, $updatedStock->totalQuantity);
    }
}
