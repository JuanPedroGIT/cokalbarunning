<?php

declare(strict_types=1);

namespace App\Application\Club\Update;

final readonly class UpdateClubMemberCommand
{
    public function __construct(
        public string $id,
        public ?string $name = null,
        public ?string $description = null,
        public ?string $bio = null,
        public ?string $photoPath = null,
        public ?bool $isActive = null,
        public ?int $sortOrder = null,
        public ?string $userId = null,
    ) {
    }
}
