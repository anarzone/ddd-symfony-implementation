<?php

declare(strict_types=1);

namespace App\Account\Domain\Repository;

use App\Account\Domain\Model\User;

/**
 * @method User|null find(\Symfony\Component\Uid\Uuid $id)
 */
interface UserRepositoryInterface
{
    public function findByEmail(string $email): ?User;

    public function save(User $user): void;

    public function findOneByReservationToken(string $token): ?User;

    /**
     * @return User[]
     */
    public function findAll(): array;
}
