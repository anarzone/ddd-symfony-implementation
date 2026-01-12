<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command;

use App\Inventory\Domain\Repository\WarehouseRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class DeactivateWarehouseHandler
{
    public function __construct(
        private WarehouseRepositoryInterface $warehouseRepository
    ) {
    }

    public function __invoke(DeactivateWarehouseMessage $message): void
    {
        $warehouse = $this->warehouseRepository->findWithLock($message->warehouseId->toRfc4122());

        if (!$warehouse) {
            throw new \InvalidArgumentException('Warehouse not found');
        }

        $warehouse->deactivate();
        $this->warehouseRepository->save($warehouse);
    }
}
