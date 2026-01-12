<?php

declare(strict_types=1);

namespace App\Account\Domain\Exception;

class UserEmailExistsException extends DomainException
{
    public function __construct(private(set) string $email)
    {
        parent::__construct(
            message: \sprintf('The email "%s" is already in use', $this->email),
            code: 422
        );
    }
}
