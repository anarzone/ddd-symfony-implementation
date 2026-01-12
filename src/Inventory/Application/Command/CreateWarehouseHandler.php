<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command;

use App\Inventory\Domain\Model\Warehouse\Warehouse;
use App\Inventory\Domain\Repository\WarehouseRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class CreateWarehouseHandler
{
    public function __construct(
        private WarehouseRepositoryInterface $warehouseRepository
    ) {
    }

    public function __invoke(CreateWarehouseMessage $message): array
    {
        $warehouse = new Warehouse(
            uuid: $message->uuid,
            name: $message->name,
            capacity: $message->capacity,
            location: $message->location,
            type: $message->type,
        );

        // Set type using domain method
        $warehouse->changeType($message->type);

        $this->warehouseRepository->save($warehouse);

        return [
            'warehouseId' => $warehouse->uuid,
            'name' => $warehouse->name,
        ];
    }
}
