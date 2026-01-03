<?php

declare(strict_types=1);

namespace App\Account\Application\Command;

use App\Account\Domain\Model\User;

final readonly class GenerateApiTokenMessage
{
    public function __construct(
        public User $user,
        public ?string $description = null,
        public ?int $expiresInDays = null
    ) {
    }
}
