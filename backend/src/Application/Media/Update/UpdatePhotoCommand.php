<?php

declare(strict_types=1);

namespace App\Application\Media\Update;

final readonly class UpdatePhotoCommand
{
    public function __construct(
        public string $id,
        public ?string $altText = null,
        public ?bool $isFeatured = null,
        public ?int $sortOrder = null,
        public ?string $raceEditionId = null,
    ) {
    }
}
