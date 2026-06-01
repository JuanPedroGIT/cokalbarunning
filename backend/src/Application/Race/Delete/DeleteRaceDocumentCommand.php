<?php

declare(strict_types=1);

namespace App\Application\Race\Delete;

final readonly class DeleteRaceDocumentCommand
{
    public function __construct(
        public string $id,
    ) {
    }
}
