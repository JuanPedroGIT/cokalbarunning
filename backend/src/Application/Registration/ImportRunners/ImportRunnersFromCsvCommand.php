<?php

declare(strict_types=1);

namespace App\Application\Registration\ImportRunners;

final readonly class ImportRunnersFromCsvCommand
{
    public function __construct(
        public string $raceEditionId,
        public string $csvContent,
    ) {
    }
}
