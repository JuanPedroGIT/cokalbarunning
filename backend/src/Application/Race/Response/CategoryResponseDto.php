<?php

declare(strict_types=1);

namespace App\Application\Race\Response;

use App\Domain\Race\Entity\Category;

final readonly class CategoryResponseDto
{
    public function __construct(
        public string $id,
        public string $name,
        public ?int $minAge,
        public ?int $maxAge,
        public float $distanceKm,
        public ?string $gender,
    ) {
    }

    public static function fromDomain(Category $category): self
    {
        return new self(
            id: $category->id(),
            name: $category->name(),
            minAge: $category->minAge(),
            maxAge: $category->maxAge(),
            distanceKm: $category->distance()->kilometers(),
            gender: $category->gender(),
        );
    }

    /**
     * @param Category[] $categories
     * @return self[]
     */
    public static function fromDomainList(array $categories): array
    {
        return array_map(fn (Category $c) => self::fromDomain($c), $categories);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'minAge' => $this->minAge,
            'maxAge' => $this->maxAge,
            'distanceKm' => $this->distanceKm,
            'gender' => $this->gender,
        ];
    }

    /**
     * @param self[] $dtos
     * @return array<int, array<string, mixed>>
     */
    public static function listToArray(array $dtos): array
    {
        return array_map(fn (self $dto) => $dto->toArray(), $dtos);
    }
}
