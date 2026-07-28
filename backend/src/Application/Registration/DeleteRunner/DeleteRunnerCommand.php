<?php

declare(strict_types=1);

namespace App\Application\Registration\DeleteRunner;

final readonly class DeleteRunnerCommand
{
    public function __construct(
        public string $id,
    ) {
    }
}
