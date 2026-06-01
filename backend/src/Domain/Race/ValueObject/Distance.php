<?php

declare(strict_types=1);

namespace App\Domain\Race\ValueObject;

use App\Domain\Shared\Exception\InvalidValueException;

final readonly class Distance
{
    public function __construct(private float $kilometers)
    {
        if ($kilometers <= 0) {
            throw new InvalidValueException('Distance must be greater than 0');
        }
    }

    public static function fromKilometers(float $km): self
    {
        return new self($km);
    }

    public function kilometers(): float
    {
        return $this->kilometers;
    }

    public function meters(): int
    {
        return (int) round($this->kilometers * 1000);
    }

    public function format(): string
    {
        if ($this->kilometers < 1) {
            return $this->meters() . ' m';
        }

        return $this->kilometers . ' km';
    }
}
