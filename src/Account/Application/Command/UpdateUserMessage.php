<?php

declare(strict_types=1);

namespace App\Account\Application\Command;

use Symfony\Component\Uid\UuidV7;

class UpdateUserMessage
{
    public function __construct(
        public UuidV7 $uuid,
        public ?string $email = null,
        public ?array $roles = ['ROLE_USER'],
    ) {
    }
}
