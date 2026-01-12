<?php

declare(strict_types=1);

namespace App\Account\Application\Dto;

readonly class CreateUserDto
{
    public function __construct(
        public string $id,
        public string $email,
        public array $roles,
        public string $createdAt
    ) {
    }
}
