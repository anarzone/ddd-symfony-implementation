<?php

declare(strict_types=1);

namespace App\Account\Application\Query;

use App\Account\Domain\Model\User;

final readonly class ListApiTokensQuery
{
    public function __construct(
        public User $user
    ) {
    }
}
