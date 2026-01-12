<?php

declare(strict_types=1);

namespace App\Account\Application\Command;

use Symfony\Component\Uid\UuidV7;

final readonly class CreateUserMessage
{
    public function __construct(
        public UuidV7 $uuid,
        public string $email,
        public string $password,
        public array $roles = ['ROLE_USER']
    ) {
    }
}
