<?php

declare(strict_types=1);

namespace App\Application\Club\UploadPhoto;

final readonly class UploadClubMemberPhotoCommand
{
    public function __construct(
        public string $memberId,
        public string $originalName,
        public string $mimeType,
        public string $tmpPath,
    ) {
    }
}
