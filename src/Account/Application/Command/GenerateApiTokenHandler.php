<?php

declare(strict_types=1);

namespace App\Account\Application\Command;

use App\Account\Domain\Model\ApiToken;
use App\Account\Domain\Repository\ApiTokenRepositoryInterface;
use App\Account\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\UuidV7;

#[AsMessageHandler]
readonly class GenerateApiTokenHandler
{
    public function __construct(
        private ApiTokenRepositoryInterface $apiTokenRepository,
        private UserRepositoryInterface $userRepository
    ) {
    }

    public function __invoke(GenerateApiTokenMessage $message): array
    {
        $plainToken = bin2hex(random_bytes(32));

        $expiresAt = null;
        if ($message->expiresInDays !== null) {
            $expiresAt = new \DateTimeImmutable("+$message->expiresInDays days");
        }

        // Fetch a fresh managed user entity from the database
        $user = $this->userRepository->find($message->user->getUuid());

        if (!$user) {
            throw new \RuntimeException('User not found');
        }

        $apiToken = new ApiToken(
            uuid: new UuidV7(),
            token: $plainToken,
            user: $user,
            description: $message->description,
            expiresAt: $expiresAt
        );

        $this->apiTokenRepository->save($apiToken);

        return [
            'token' => $plainToken,
            'tokenId' => $apiToken->uuid->toRfc4122(),
        ];
    }
}
