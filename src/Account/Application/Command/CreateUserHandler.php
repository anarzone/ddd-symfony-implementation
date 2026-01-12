<?php

declare(strict_types=1);

namespace App\Account\Application\Command;

use App\Account\Domain\Model\User;
use App\Account\Domain\Repository\UserRepositoryInterface;
use App\Account\Domain\Service\UserUniqueEmailChecker;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsMessageHandler]
class CreateUserHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private UserPasswordHasherInterface $hasher,
        private UserUniqueEmailChecker $uniqueEmailChecker,
    ) {
    }

    public function __invoke(CreateUserMessage $message): void
    {
        $this->uniqueEmailChecker->ensureEmailIsUnique($message->email);

        $user = new User(
            uuid: $message->uuid,
            email: $message->email,
            password: $message->password,
            passwordHasher: $this->hasher,
            roles: $message->roles
        );

        $this->userRepository->save($user);
    }
}
