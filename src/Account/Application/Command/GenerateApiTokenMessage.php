<?php

namespace App\Account\Application\Command;

use App\Account\Domain\Model\User;

final readonly class GenerateApiTokenMessage
{
    public function __construct(
        public User $user,
        public ?string $description = null,
        public ?int $expiresInDays = null
    )
    {
    }
}
