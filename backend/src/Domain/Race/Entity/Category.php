<?php

declare(strict_types=1);

namespace App\Domain\Race\Entity;

use App\Domain\Race\ValueObject\Distance;

final class Category
{
    public function __construct(
        private string $id,
        private string $name,
        private ?int $minAge,
        private ?int $maxAge,
        private Distance $distance,
        private ?string $gender = null,
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function minAge(): ?int
    {
        return $this->minAge;
    }

    public function maxAge(): ?int
    {
        return $this->maxAge;
    }

    public function distance(): Distance
    {
        return $this->distance;
    }

    public function gender(): ?string
    {
        return $this->gender;
    }

    public function update(string $name, ?int $minAge, ?int $maxAge, Distance $distance, ?string $gender): void
    {
        $this->name = $name;
        $this->minAge = $minAge;
        $this->maxAge = $maxAge;
        $this->distance = $distance;
        $this->gender = $gender;
    }
}
