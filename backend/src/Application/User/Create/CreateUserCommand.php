<?php

declare(strict_types=1);

namespace App\Application\User\Create;

final readonly class CreateUserCommand
{
    public function __construct(
        public string $email,
        public string $password,
        public string $firstName = '',
        public string $lastName = '',
        public array $roles = ['ROLE_EDITOR'],
    ) {
    }
}
