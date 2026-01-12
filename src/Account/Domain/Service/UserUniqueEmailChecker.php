<?php

declare(strict_types=1);

namespace App\Account\Domain\Service;

use App\Account\Domain\Exception\UserEmailExistsException;
use App\Account\Domain\Model\User;
use App\Account\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\Uid\UuidV7;

class UserUniqueEmailChecker
{
    public function __construct(private UserRepositoryInterface $userRepository)
    {
    }

    public function ensureEmailIsUnique(string $email, ?UuidV7 $ignoreUuid = null): void
    {
        $existing = $this->userRepository->findByEmail($email);

        if (!$existing instanceof User) {
            return;
        }

        if ($ignoreUuid && $existing->getUuid()->equals($ignoreUuid)) {
            return;
        }

        throw new UserEmailExistsException($email);
    }
}
