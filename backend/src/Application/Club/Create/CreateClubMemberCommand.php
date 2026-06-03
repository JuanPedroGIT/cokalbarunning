<?php

declare(strict_types=1);

namespace App\Application\Club\Create;

final readonly class CreateClubMemberCommand
{
    public function __construct(
        public string $name,
        public ?string $description = null,
        public ?string $bio = null,
        public ?string $photoPath = null,
        public bool $isActive = true,
        public int $sortOrder = 0,
        public ?string $userId = null,
    ) {
    }
}
