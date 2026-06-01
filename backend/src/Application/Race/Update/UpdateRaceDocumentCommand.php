<?php

declare(strict_types=1);

namespace App\Application\Race\Update;

final readonly class UpdateRaceDocumentCommand
{
    public function __construct(
        public string $id,
        public ?string $name = null,
        public ?string $type = null,
        public ?string $editionId = null,
    ) {
    }
}
