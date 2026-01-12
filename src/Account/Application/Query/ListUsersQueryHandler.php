<?php

declare(strict_types=1);

namespace App\Account\Application\Query;

use App\Account\Application\Dto\Response\UserResponseDto;
use App\Account\Application\Dto\Response\UsersListDto;
use App\Account\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class ListUsersQueryHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {
    }

    public function __invoke(ListUsersQuery $query): UsersListDto
    {
        $users = $this->userRepository->findAll();

        $userDtos = array_map(function ($user) {
            return new UserResponseDto(
                uuid: $user->getUuid(),
                email: $user->getEmail(),
                roles: $user->getRoles(),
                createdAt: $user->getCreatedAt()->format(\DateTime::ATOM),
            );
        }, $users);

        return new UsersListDto(userDtos: $userDtos);
    }
}
