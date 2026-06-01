<?php

declare(strict_types=1);

namespace App\Application\Race\Delete;

final class DeleteRaceEditionCommand
{
    public function __construct(
        public string $id,
    ) {
    }
}
