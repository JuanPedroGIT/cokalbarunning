<?php

declare(strict_types=1);

namespace App\Application\Club\GetMyProfile;

final readonly class GetMyClubProfileQuery
{
    public function __construct(
        public string $userId,
    ) {
    }
}
