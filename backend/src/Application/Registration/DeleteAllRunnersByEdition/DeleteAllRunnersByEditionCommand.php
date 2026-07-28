<?php

declare(strict_types=1);

namespace App\Application\Registration\DeleteAllRunnersByEdition;

final readonly class DeleteAllRunnersByEditionCommand
{
    public function __construct(
        public string $editionId,
    ) {
    }
}
