<?php

declare(strict_types=1);

namespace App\Application\Club\Update;

final class UpdateSponsorCommand
{
    public function __construct(
        public string $id,
        public ?string $name = null,
        public ?string $logoUrl = null,
        public ?string $website = null,
        public ?string $tier = null,
        public ?bool $isActive = null,
        public ?int $sortOrder = null,
        public ?string $message = null,
    ) {
    }
}
