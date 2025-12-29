<?php

namespace App\Account\Application\Query;

use App\Account\Domain\Repository\ApiTokenRepositoryInterface;
use App\Account\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class ListApiTokensQueryHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private ApiTokenRepositoryInterface $apiTokenRepository
    ) {
    }

    public function __invoke(ListApiTokensQuery $message)
    {
        $tokens = $this->apiTokenRepository->findActiveTokens($message->user);

        return array_map(function ($token) {
            return [
                'id' => $token->id,
                'description' => $token->description,
                'createdAt' => $token->createdAt->format(\DateTime::ATOM),
                'expiresAt' => $token->expiresAt?->format(\DateTime::ATOM),
                'lastUsedAt' => $token->lastUsedAt?->format(\DateTime::ATOM)
            ];
        }, $tokens);
    }
}
