<?php

declare(strict_types=1);

namespace App\Application\User\Update;

final readonly class UpdateUserCommand
{
    public function __construct(
        public string $id,
        public ?string $email = null,
        public ?string $password = null,
        public ?string $firstName = null,
        public ?string $lastName = null,
        public ?array $roles = null,
    ) {
    }
}
