<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\DataFixtures;

use App\Account\Domain\Model\User;
use App\Account\Infrastructure\DataFixtures\UserFixtures;
use App\Inventory\Domain\Model\Stock\Reservation;
use App\Inventory\Domain\Model\Stock\Stock;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Uid\UuidV7;

class ReservationFixtures extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Fetch all stocks and users from database
        $stocks = $manager->getRepository(Stock::class)->findAll();
        $users = $manager->getRepository(User::class)->findAll();

        if (empty($stocks) || empty($users)) {
            throw new \RuntimeException('Stocks and users must be loaded first');
        }

        $reservationCount = 0;
        $totalReservations = 2000;

        // Create reservations with different statuses and time periods
        for ($i = 0; $i < $totalReservations; ++$i) {
            $stock = $stocks[array_rand($stocks)];
            $user = $users[array_rand($users)];

            // Random quantity between 1 and 20
            $quantity = rand(1, 20);

            // Set creation time to sometime in the past 30 days
            $daysAgo = rand(0, 30);
            $hoursAgo = rand(0, 23);
            $createdAt = new \DateTimeImmutable("-$daysAgo days -$hoursAgo hours");

            // Random validity period: 15, 30, 60, or 120 minutes
            $validityOptions = [15, 30, 60, 120];
            $minutesValid = $validityOptions[array_rand($validityOptions)];

            // Set expiresAt based on createdAt + validity
            $expiresAt = $createdAt->modify("+$minutesValid minutes");

            // Create reservation using factory method
            $reservation = Reservation::createWithCustomExpiry(
                uuid: new UuidV7(),
                stock: $stock,
                quantity: $quantity,
                user: $user,
                expiresAt: $expiresAt,
                createdAt: $createdAt
            );

            // Determine status and apply changes
            $now = new \DateTimeImmutable();
            $rand = rand(1, 100);

            if ($expiresAt < $now) {
                // Expired reservation
                if ($rand > 85) {
                    // 15% were cancelled
                    $reservation->cancel();
                }
            // 85% remain ACTIVE but expired (isExpired() returns true)
            // Note: Can't convert expired reservations to sale due to domain rule
            } else {
                // Still active
                if ($rand > 70 && $rand <= 85) {
                    // 15% are converted to sale
                    $reservation->convertToSale('ORDER-'.$this->generateOrderReference());
                } elseif ($rand > 85) {
                    // 15% were cancelled
                    $reservation->cancel();
                }
                // 70% remain active
            }

            $manager->persist($reservation);
            ++$reservationCount;

            // Flush every 10 items to avoid memory issues
            if ($reservationCount % 10 === 0) {
                $manager->flush();
            }

            // Clear every 100 items to avoid memory issues
            if ($reservationCount % 100 === 0) {
                $manager->clear();
                // Re-fetch stocks and users after clear
                $stocks = $manager->getRepository(Stock::class)->findAll();
                $users = $manager->getRepository(User::class)->findAll();
            }
        }

        // Final flush for any remaining items
        $manager->flush();
    }

    private function generateOrderReference(): string
    {
        return \sprintf('ORD-%d-%s', rand(10000, 99999), bin2hex(random_bytes(4)));
    }

    public static function getGroups(): array
    {
        return ['all'];
    }

    public function getDependencies(): array
    {
        return [
            StockFixtures::class,
            UserFixtures::class,
        ];
    }
}
