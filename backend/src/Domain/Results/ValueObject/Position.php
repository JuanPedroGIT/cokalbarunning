<?php

declare(strict_types=1);

namespace App\Domain\Results\ValueObject;

use App\Domain\Shared\Exception\InvalidValueException;

final readonly class Position
{
    public function __construct(private int $value)
    {
        if ($value < 1) {
            throw new InvalidValueException('Position must be greater than or equal to 1');
        }
    }

    public static function fromInt(int $value): self
    {
        return new self($value);
    }

    public function value(): int
    {
        return $this->value;
    }
}
