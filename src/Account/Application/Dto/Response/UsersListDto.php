<?php

declare(strict_types=1);

namespace App\Account\Application\Dto\Response;

class UsersListDto
{
    public function __construct(
        /**
         * @var array<UserResponseDto> $userDtos
         */
        public array $userDtos,
    ) {
    }
}
