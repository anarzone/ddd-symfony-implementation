<?php

declare(strict_types=1);

namespace App\Account\Application\Dto\Response;

use Symfony\Component\Uid\UuidV7;

class UserResponseDto
{
    public function __construct(
        public UuidV7 $uuid,
        public string $email,
        public array $roles,
        public string $createdAt
    ) {
    }
}
