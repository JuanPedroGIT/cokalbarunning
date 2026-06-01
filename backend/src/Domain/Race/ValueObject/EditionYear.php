<?php

declare(strict_types=1);

namespace App\Domain\Race\ValueObject;

use App\Domain\Shared\Exception\InvalidValueException;

final readonly class EditionYear
{
    public function __construct(private int $year)
    {
        $currentYear = (int) date('Y');
        if ($year < 2000 || $year > $currentYear + 2) {
            throw new InvalidValueException(
                sprintf('Edition year must be between 2000 and %d', $currentYear + 2)
            );
        }
    }

    public static function fromInt(int $year): self
    {
        return new self($year);
    }

    public function value(): int
    {
        return $this->year;
    }

    public function __toString(): string
    {
        return (string) $this->year;
    }
}
