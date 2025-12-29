<?php

namespace App\Inventory\Application\Command;

use App\Inventory\Domain\Model\Warehouse\Location;
use App\Inventory\Domain\Model\Warehouse\Warehouse;
use App\Inventory\Domain\Repository\WarehouseRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class CreateWarehouseHandler
{
    public function __construct(
        private WarehouseRepositoryInterface $warehouseRepository
    ) {}

    public function __invoke(CreateWarehouseMessage $message): array
    {
        $location = new Location(
            address: $message->address,
            city: $message->city,
            postalCode: $message->postalCode,
            latitude: $message->latitude,
            longitude: $message->longitude
        );

        $warehouse = new Warehouse(
            name: $message->name,
            capacity: $message->capacity,
            location: $location
        );

        // Set type using domain method
        $warehouse->changeType($message->type);

        $this->warehouseRepository->save($warehouse);

        return [
            'warehouseId' => $warehouse->id,
            'name' => $warehouse->name
        ];
    }
}
