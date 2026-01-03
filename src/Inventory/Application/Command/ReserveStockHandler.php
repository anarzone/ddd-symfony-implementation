<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command;

use App\Account\Domain\Repository\UserRepositoryInterface;
use App\Inventory\Domain\Exception\InsufficientStockException;
use App\Inventory\Domain\Repository\StockRepositoryInterface;
use App\Inventory\Domain\Service\StockDomainService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class ReserveStockHandler
{
    public function __construct(
        private StockRepositoryInterface $stockRepository,
        private UserRepositoryInterface $userRepository,
        private StockDomainService $domainService
    ) {
    }

    /**
     * @throws InsufficientStockException
     */
    public function __invoke(ReserveStockMessage $message): void
    {
        $stock = $this->stockRepository->findWithLock($message->stockId);

        if (!$stock) {
            throw new \InvalidArgumentException('Stock not found');
        }

        // Find user
        $user = $this->userRepository->find($message->userId);
        if (!$user) {
            throw new \InvalidArgumentException('User not found');
        }

        try {
            $this->domainService->reserveStock($stock, $user, $message->quantity, $message->minutesValid);
            $this->stockRepository->save($stock);
        } catch (InsufficientStockException $e) {
            throw new InsufficientStockException($e->getMessage());
        }
    }
}
