<?php

namespace App\Account\Domain\Repository;

use App\Account\Domain\Model\ApiToken;
use App\Account\Domain\Model\User;

interface ApiTokenRepositoryInterface
{
    public function findByToken(string $token): ?ApiToken;
    public function findActiveTokens(User $user): array;

    public function save(ApiToken $token): void;

    public function revoke(ApiToken $token): void;

    public function revokeAllForUser(User $user): void;

    public function revokeExpiredTokens(): int;
}
