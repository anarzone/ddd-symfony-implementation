<?php

declare(strict_types=1);

namespace App\Account\Infrastructure\DataFixtures;

use App\Account\Domain\Model\ApiToken;
use App\Account\Domain\Model\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ApiTokenFixtures extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Create API tokens for all users
        // Admin user gets 3 tokens
        $admin = $this->getReference('user-1', User::class);
        $this->createToken($manager, $admin, 'admin-main-token', '+6 months');
        $this->createToken($manager, $admin, 'admin-testing-token', '+1 month');
        $this->createToken($manager, $admin, 'admin-expired-token', '-1 month'); // Expired

        // Regular users get 1-2 tokens each
        for ($i = 2; $i <= 10; ++$i) {
            $user = $this->getReference('user-'.$i, User::class);
            $this->createToken($manager, $user, 'main-api-token', '+3 months');

            // Some users get a second token
            if ($i % 3 === 0) {
                $this->createToken($manager, $user, 'secondary-api-token', '+6 months');
            }

            // Some users have an expired token
            if ($i % 4 === 0) {
                $this->createToken($manager, $user, 'expired-token', '-1 week');
            }
        }

        $manager->flush();
    }

    private function createToken(ObjectManager $manager, User $user, string $description, string $modifier): void
    {
        $plainToken = bin2hex(random_bytes(32));
        $expiresAt = null;

        if ($modifier !== null) {
            $expiresAt = new \DateTimeImmutable($modifier);
        }

        $token = new ApiToken($user, $plainToken, $description, $expiresAt);
        $manager->persist($token);
    }

    public static function getGroups(): array
    {
        return ['all'];
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
        ];
    }
}
