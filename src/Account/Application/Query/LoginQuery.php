<?php

declare(strict_types=1);

namespace App\Account\Application\Query;

use App\Account\Application\Dto\Request\LoginRequestDto;

final readonly class LoginQuery
{
    public function __construct(
        public LoginRequestDto $loginRequest
    ) {
    }
}
