<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Persistence\Doctrine;

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

    public function find(UuidV7 $id)
    {
        $key = 'stock_'.$id;

        return $this->cache->get($key, function (ItemInterface $item) use ($id) {
            $item->tag(['stock_id_'.$id]);
            $item->expiresAfter(3600);

            return $this->decoratedRepository->find($id);
        });
    }

    public function findAllWithWarehouse(): ?array
    {
        $key = 'stock_with_warehouse_all';

        return $this->cache->get($key, function (ItemInterface $item) use ($key) {
            $item->tag([$key]);
            $item->expiresAfter(3600);

            return $this->decoratedRepository->findAllWithWarehouse();
        });
    }

    public function save(Stock $stock): void
    {
    }

    public function findWithLock(UuidV7 $id): ?Stock
    {
        return $this->decoratedRepository->findWithLock($id);
    }
}
