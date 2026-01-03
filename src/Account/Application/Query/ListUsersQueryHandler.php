<?php

declare(strict_types=1);

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
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
                'createdAt' => $user->getCreatedAt()->format(\DateTime::ATOM),
            ];
        }, $users);
    }
}
