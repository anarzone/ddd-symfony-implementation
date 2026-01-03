<?php

declare(strict_types=1);

namespace App\Account\Infrastructure\DataFixtures;

use App\Account\Domain\Model\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture implements FixtureGroupInterface
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $admin = new User('admin@fastreserve.com', 'admin123', ['ROLE_ADMIN']);
        $admin->promoteToAdmin();

        $manager->persist($admin);
        $manager->flush();

        $this->addReference('admin-user', $admin);
    }

    public static function getGroups(): array
    {
        return ['all'];
    }
}
