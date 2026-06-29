<?php

declare(strict_types=1);

namespace App\Application\Race\UploadDocument;

final readonly class UploadRaceDocumentCommand
{
    public function __construct(
        public string $tmpPath,
        public string $originalName,
        public string $mimeType,
        public string $name,
        public string $type,
        public ?string $editionId = null,
    ) {
    }
}
