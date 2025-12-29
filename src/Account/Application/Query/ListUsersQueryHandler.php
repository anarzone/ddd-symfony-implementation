<?php

namespace App\Account\Application\Query;

use App\Account\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class ListUsersQueryHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {
    }

    public function __invoke(ListUsersQuery $query): array
    {
        $users = $this->userRepository->findAll();

        return array_map(function ($user) {
            return [
                'id' => $user->id,
                'email' => $user->email,
                'roles' => $user->getRoles(),
                'createdAt' => $user->createdAt->format(\DateTime::ATOM)
            ];
        }, $users);
    }
}
