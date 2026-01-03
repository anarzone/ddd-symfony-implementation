<?php

declare(strict_types=1);

namespace App\Inventory\Application\Query;

use App\Inventory\Application\Dto\StockLevelDto;
use App\Inventory\Domain\Repository\StockRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class GetStockLevelQueryHandler
{
    public function __construct(
        private StockRepositoryInterface $stockRepository
    ) {
    }

    public function __invoke(GetStockLevelQuery $query): StockLevelDto
    {
        $stock = $this->stockRepository->find($query->stockId);

        if (!$stock) {
            throw new \InvalidArgumentException('Stock not found');
        }

        return new StockLevelDto(
            stockId: $stock->id !== null ? $stock->id->toRfc4122() : '',
            skuCode: $stock->sku->code,
            skuName: $stock->sku->name,
            totalQuantity: $stock->totalQuantity,
            availableQuantity: $stock->getAvailableQuantity(),
            reservedQuantity: $stock->getReservedQuantity(),
            warehouseName: $stock->warehouse->name,
            location: $stock->warehouse->getLocation()->city.', '.$stock->warehouse->getLocation()->address
        );
    }
}
