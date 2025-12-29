<?php

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
    ): Reservation
    {
        // Check if user has too many active reservations (business rule)
        $activeReservations = $user->reservations->filter(
            fn(Reservation $r) => $r->isActive()
        );

        if ($activeReservations->count() >= 10) {
            throw new InsufficientStockException(
                'User has too many active reservations (max 10)'
            );
        }

        if ($stock->getAvailableQuantity() < $quantity) {
            throw new InsufficientStockException(
                sprintf(
                    'Insufficient stock: %d available, %d requested',
                    $stock->getAvailableQuantity(),
                    $quantity
                )
            );
        }

        $reservation = new Reservation($stock, $quantity, $user, $minutesValid);

        // Add to stock
        $stock->reservations->add($reservation);

        return $reservation;
    }
}
