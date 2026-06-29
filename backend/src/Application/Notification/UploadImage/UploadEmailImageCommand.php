<?php

declare(strict_types=1);

namespace App\Application\Notification\UploadImage;

final readonly class UploadEmailImageCommand
{
    public function __construct(
        public string $editionId,
        public string $type,
        public string $tmpPath,
        public string $originalName,
        public string $mimeType,
    ) {
    }
}
