<?php

declare(strict_types=1);

namespace App\Account\Application\Command;

use App\Account\Domain\Model\User;
use App\Account\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class CreateUserHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function __invoke(CreateUserMessage $message): array
    {
        // Create new user
        $user = new User($message->email, $message->password, $message->roles);

        $this->userRepository->save($user);

        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
            'createdAt' => $user->getCreatedAt()->format(\DateTime::ATOM),
        ];
    }
}
