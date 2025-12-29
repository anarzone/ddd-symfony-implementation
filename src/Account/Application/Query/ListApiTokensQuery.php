<?php

namespace App\Account\Application\Query;

use App\Account\Domain\Model\User;

final readonly class ListApiTokensQuery
{
    public function __construct(
        public User $user
    ) {
    }
}
