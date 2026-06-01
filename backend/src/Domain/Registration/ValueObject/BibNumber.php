<?php

declare(strict_types=1);

namespace App\Domain\Registration\ValueObject;

use App\Domain\Shared\Exception\InvalidValueException;

final readonly class BibNumber
{
    public function __construct(private string $value)
    {
        if (empty(trim($value))) {
            throw new InvalidValueException('Bib number cannot be empty');
        }

        if (!preg_match('/^[A-Z0-9\-]+$/i', $value)) {
            throw new InvalidValueException('Bib number contains invalid characters');
        }
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }
}
