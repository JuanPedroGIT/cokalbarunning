<?php

declare(strict_types=1);

namespace App\Application\Media\Upload;

final readonly class UploadPhotoCommand
{
    public function __construct(
        public string $originalPath,
        public ?string $thumbPath = null,
        public ?string $altText = null,
        public ?string $raceEditionId = null,
        public bool $isFeatured = false,
        public int $sortOrder = 0,
    ) {
    }
}
