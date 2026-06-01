<?php

declare(strict_types=1);

namespace App\Application\Race\Create;

final readonly class CreateRaceDocumentCommand
{
    public function __construct(
        public string $name,
        public string $type,
        public string $filePath,
        public ?string $editionId = null,
    ) {
    }
}
