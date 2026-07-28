<?php

declare(strict_types=1);

namespace App\Application\Registration\GetRunnersByEdition;

final readonly class GetRunnersByEditionQuery
{
    public function __construct(
        public string $editionId,
    ) {
    }
}
