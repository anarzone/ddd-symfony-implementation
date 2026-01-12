<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Persistence\Doctrine\Cache;

use App\Inventory\Domain\Model\Warehouse\Warehouse;
use App\Inventory\Domain\Repository\WarehouseRepositoryInterface;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[AsDecorator(decorates: WarehouseRepositoryInterface::class)]
class CachedWarehouseRepository implements WarehouseRepositoryInterface
{
    public function __construct(
        private WarehouseRepositoryInterface $decoratedRepository,
        private TagAwareCacheInterface $cache,
    ) {
    }

    public function find(string $id): ?Warehouse
    {
        $key = 'warehouse_'.$id;

        return $this->cache->get($key, function (ItemInterface $item) use ($id) {
            $item->tag(['warehouse_id_'.$id, 'warehouse_all']);
            $item->expiresAfter(3600); // 1 hour

            return $this->decoratedRepository->find($id);
        });
    }

    public function findAll(): array
    {
        $key = 'warehouse_all';

        return $this->cache->get($key, function (ItemInterface $item) {
            $item->tag(['warehouse_all']);
            $item->expiresAfter(3600); // 1 hour

            return $this->decoratedRepository->findAll();
        });
    }

    public function save(Warehouse $warehouse): void
    {
        $this->decoratedRepository->save($warehouse);

        // Invalidate cache for this specific warehouse and the all list
        $tags = ['warehouse_all'];
        if ($warehouse->uuid !== null) {
            $tags[] = 'warehouse_id_'.$warehouse->uuid;
        }

        $this->cache->invalidateTags($tags);
    }

    public function findWithLock(string $id): ?Warehouse
    {
        // Never cache locked queries - always get fresh data
        return $this->decoratedRepository->findWithLock($id);
    }
}
