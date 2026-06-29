<?php

declare(strict_types=1);

namespace App\Application\User\Delete;

final readonly class DeleteUserCommand
{
    public function __construct(
        public string $id,
    ) {
    }
}
