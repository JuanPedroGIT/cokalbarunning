<?php

declare(strict_types=1);

namespace App\Application\Media\UploadCover;

final readonly class UploadBlogCoverCommand
{
    public function __construct(
        public string $postId,
        public string $tmpPath,
        public string $originalName,
        public string $mimeType,
    ) {
    }
}
