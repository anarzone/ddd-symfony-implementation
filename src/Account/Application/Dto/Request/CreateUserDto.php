<?php

declare(strict_types=1);

namespace App\Account\Application\Dto\Request;

use Symfony\Component\Validator\Constraints as Assert;

readonly class CreateUserDto
{
    public function __construct(
        #[Assert\Email]
        #[Assert\NotBlank]
        public string $email,
        #[Assert\NotBlank]
        #[Assert\Length(min: 8)]
        public string $password,
        public array $roles = ['ROLE_USER'],
    ) {
    }
}
