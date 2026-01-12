<?php

declare(strict_types=1);

namespace App\Account\Application\Command;

use App\Account\Domain\Repository\UserRepositoryInterface;
use App\Account\Domain\Service\UserUniqueEmailChecker;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class UpdateUserHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private UserUniqueEmailChecker $uniqueEmailChecker,
    ) {
    }

    public function __invoke(UpdateUserMessage $message): void
    {
        if ($message->email !== null) {
            $this->uniqueEmailChecker->ensureEmailIsUnique($message->email, $message->uuid);
        }

        $user = $this->userRepository->find($message->uuid);

        if ($message->email) {
            $user->changeEmail($message->email);
        }

        if ($message->roles) {
            $user->roles = $message->roles;
        }

        $this->userRepository->save($user);
    }
}
