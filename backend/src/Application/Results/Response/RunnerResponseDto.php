<?php

declare(strict_types=1);

namespace App\Application\Results\Response;

use App\Domain\Registration\Entity\Runner;

final readonly class RunnerResponseDto
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public ?string $club,
    ) {
    }

    public static function fromDomain(Runner $runner): self
    {
        return new self(
            firstName: $runner->firstName(),
            lastName: $runner->lastName(),
            club: $runner->club(),
        );
    }

    public function toArray(): array
    {
        return [
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'club' => $this->club,
        ];
    }
}
