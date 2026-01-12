<?php

declare(strict_types=1);

namespace App\Account\Application\Query;

use App\Account\Domain\Exception\UserNotFoundException;
use App\Account\Domain\Repository\ApiTokenRepositoryInterface;
use App\Account\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsMessageHandler]
readonly class LoginQueryHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private ApiTokenRepositoryInterface $apiTokenRepository,
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function __invoke(LoginQuery $query): array
    {
        $dto = $query->loginRequest;

        // Find user by email
        $user = $this->userRepository->findByEmail($dto->email);

        if (!$user) {
            throw new UserNotFoundException('Invalid credentials');
        }

        // Verify password
        if (!$this->passwordHasher->isPasswordValid($user, $dto->password)) {
            throw new UserNotFoundException('Invalid credentials');
        }

        // Generate API token directly
        $plainToken = bin2hex(random_bytes(32));

        $expiresAt = null;
        if ($dto->expiresInDays !== null) {
            $expiresAt = new \DateTimeImmutable("+$dto->expiresInDays days");
        }

        // Fetch a fresh managed user entity from the database
        $managedUser = $this->userRepository->find($user->getUuid());

        if (!$managedUser) {
            throw new UserNotFoundException('User not found');
        }

        $apiToken = new \App\Account\Domain\Model\ApiToken(
            uuid: new \Symfony\Component\Uid\UuidV7(),
            token: $plainToken,
            user: $managedUser,
            description: $dto->description ?? 'Created via login',
            expiresAt: $expiresAt
        );

        $this->apiTokenRepository->save($apiToken);

        return [
            'user' => [
                'id' => $user->getUuid(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
            ],
            'token' => $plainToken,
            'tokenId' => $apiToken->uuid->toRfc4122(),
        ];
    }
}
