<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Service;

use App\Account\Domain\Model\User;
use App\Inventory\Domain\Exception\InsufficientStockException;
use App\Inventory\Domain\Model\Stock\Reservation;
use App\Inventory\Domain\Model\Stock\Stock;

class StockDomainService
{
    /**
     * @throws InsufficientStockException
     */
    public function reserveStock(
        Stock $stock,
        User $user,
        int $quantity,
        int $minutesValid = 15
    ): Reservation {
        $activeReservations = $user->activeReservations();

        if ($activeReservations->count() >= 10) {
            throw new InsufficientStockException(
                'User has too many active reservations (max 10)'
            );
        }

        return $stock->reserve($quantity, $user, $minutesValid);
    }
}
