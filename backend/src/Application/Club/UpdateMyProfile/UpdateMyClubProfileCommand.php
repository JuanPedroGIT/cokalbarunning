<?php

declare(strict_types=1);

namespace App\Application\Club\UpdateMyProfile;

final readonly class UpdateMyClubProfileCommand
{
    public function __construct(
        public string $userId,
        public ?string $name = null,
        public ?string $bio = null,
    ) {
    }
}
