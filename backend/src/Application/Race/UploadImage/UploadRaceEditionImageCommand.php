<?php

declare(strict_types=1);

namespace App\Application\Race\UploadImage;

final readonly class UploadRaceEditionImageCommand
{
    public function __construct(
        public string $editionId,
        public string $type, // 'poster' or 'shirt'
        public string $originalName,
        public string $mimeType,
        public string $tmpPath,
    ) {
    }
}
