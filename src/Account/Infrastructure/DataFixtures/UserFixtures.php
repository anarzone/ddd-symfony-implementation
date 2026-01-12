<?php

declare(strict_types=1);

namespace App\Account\Infrastructure\DataFixtures;

use App\Account\Domain\Model\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\UuidV7;

class UserFixtures extends Fixture implements FixtureGroupInterface
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        // Admin user
        $admin = new User(
            uuid: new UuidV7(),
            email: 'admin@fastreserve.com',
            password: 'admin123',
            passwordHasher: $this->passwordHasher,
            roles: ['ROLE_ADMIN']
        );
        $admin->promoteToAdmin();
        $manager->persist($admin);
        $this->addReference('user-1', $admin);

        // Regular users
        $users = [
            ['email' => 'john.doe@example.com', 'password' => 'user123', 'roles' => ['ROLE_USER']],
            ['email' => 'jane.smith@example.com', 'password' => 'user123', 'roles' => ['ROLE_USER']],
            ['email' => 'bob.wilson@example.com', 'password' => 'user123', 'roles' => ['ROLE_USER']],
            ['email' => 'alice.brown@example.com', 'password' => 'user123', 'roles' => ['ROLE_USER']],
            ['email' => 'charlie.davis@example.com', 'password' => 'user123', 'roles' => ['ROLE_USER']],
            ['email' => 'diana.miller@example.com', 'password' => 'user123', 'roles' => ['ROLE_USER']],
            ['email' => 'edward.garcia@example.com', 'password' => 'user123', 'roles' => ['ROLE_USER']],
            ['email' => 'fanny.martinez@example.com', 'password' => 'user123', 'roles' => ['ROLE_USER']],
            ['email' => 'george.rodriguez@example.com', 'password' => 'user123', 'roles' => ['ROLE_USER']],
        ];

        foreach ($users as $index => $userData) {
            $user = new User(
                uuid: new UuidV7(),
                email: $userData['email'],
                password: $userData['password'],
                passwordHasher: $this->passwordHasher,
                roles: $userData['roles']
            );
            $manager->persist($user);
            $this->addReference('user-'.($index + 2), $user);
        }

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['all'];
    }
}
