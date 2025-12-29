<?php

namespace App\Account\Application\Command;

use App\Account\Domain\Model\ApiToken;
use App\Account\Domain\Repository\ApiTokenRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class GenerateApiTokenHandler
{
    public function __construct(
        private ApiTokenRepositoryInterface $apiTokenRepository
    ) {
    }

    public function __invoke(GenerateApiTokenMessage $message)
    {
        $plainToken = bin2hex(random_bytes(32));

        $expiresAt = null;
        if ($message->expiresInDays !== null) {
            $expiresAt = new \DateTimeImmutable("+$message->expiresInDays days");
        }

        $apiToken = new ApiToken(
            user: $message->user,
            token: $plainToken,
            description: $message->description,
            expiresAt: $expiresAt
        );

        $this->apiTokenRepository->save($apiToken);

        return [
            'tokenId' => $apiToken->id,
            'token' => $plainToken, // Only shown once!
            'description' => $apiToken->description,
            'expiresAt' => $apiToken->expiresAt?->format(\DateTime::ATOM),
            'createdAt' => $apiToken->createdAt->format(\DateTime::ATOM)
        ];
    }
}
