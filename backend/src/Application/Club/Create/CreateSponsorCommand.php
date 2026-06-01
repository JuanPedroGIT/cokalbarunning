<?php

declare(strict_types=1);

namespace App\Application\Club\Create;

final class CreateSponsorCommand
{
    public function __construct(
        public string $name,
        public ?string $logoUrl = null,
        public ?string $website = null,
        public string $tier = 'bronze',
        public bool $isActive = true,
        public int $sortOrder = 0,
        public ?string $message = null,
    ) {
    }
}
