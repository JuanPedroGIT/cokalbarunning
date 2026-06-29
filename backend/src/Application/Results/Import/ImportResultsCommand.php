<?php

declare(strict_types=1);

namespace App\Application\Results\Import;

final readonly class ImportResultsCommand
{
    public function __construct(
        public string $editionId,
        public string $csvPath,
    ) {
    }
}
