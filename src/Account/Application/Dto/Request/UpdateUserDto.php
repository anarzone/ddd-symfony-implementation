<?php

declare(strict_types=1);

namespace App\Account\Application\Dto\Request;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateUserDto
{
    public function __construct(
        #[Assert\Email]
        public ?string $email = null,
        public ?array $roles = null,
    ) {
    }
}
