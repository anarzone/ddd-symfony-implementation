<?php

namespace App\Account\Application\Command;

final readonly class CreateUserMessage
{
    public function __construct(
        public string $email,
        public string $password,
        public array $roles = ['ROLE_USER']
    ) {
    }
}
