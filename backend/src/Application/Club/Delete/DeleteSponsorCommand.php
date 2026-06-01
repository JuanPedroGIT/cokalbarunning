<?php

declare(strict_types=1);

namespace App\Application\Club\Delete;

final class DeleteSponsorCommand
{
    public function __construct(
        public string $id,
    ) {
    }
}
