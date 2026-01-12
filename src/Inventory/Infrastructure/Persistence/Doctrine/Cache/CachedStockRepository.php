<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Persistence\Doctrine\Cache;

use App\Inventory\Domain\Model\Stock\Stock;
use App\Inventory\Domain\Repository\StockRepositoryInterface;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\Uid\UuidV7;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[AsDecorator(decorates: StockRepositoryInterface::class)]
class CachedStockRepository implements StockRepositoryInterface
{
    public function __construct(
        private StockRepositoryInterface $decoratedRepository,
        private TagAwareCacheInterface $cache,
    ) {
    }

    public function find(UuidV7 $id): ?Stock
    {
        $key = 'stock_'.$id->toRfc4122();

        return $this->cache->get($key, function (ItemInterface $item) use ($id) {
            $item->tag(['stock_id_'.$id->toRfc4122(), 'stock_with_warehouse_all']);
            $item->expiresAfter(600); // 1 minute for stock data

            return $this->decoratedRepository->find($id);
        });
    }

    public function findAllWithWarehouse(): array
    {
        $key = 'stock_with_warehouse_all';

        return $this->cache->get($key, function (ItemInterface $item) {
            $item->tag(['stock_with_warehouse_all']);
            $item->expiresAfter(600); // 1 minute

            return $this->decoratedRepository->findAllWithWarehouse();
        });
    }

    public function save(Stock $stock): void
    {
        $this->decoratedRepository->save($stock);

        $this->cache->invalidateTags(['stock_id_'.$stock->uuid, 'stock_with_warehouse_all']);
    }

    public function findWithLock(UuidV7 $id): ?Stock
    {
        return $this->decoratedRepository->findWithLock($id);
    }
}
