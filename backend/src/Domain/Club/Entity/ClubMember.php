<?php

declare(strict_types=1);

namespace App\Domain\Club\Entity;

final class ClubMember
{
    public function __construct(
        private string $id,
        private string $name,
        private ?string $description = null,
        private ?string $photoPath = null,
        private bool $isActive = true,
        private int $sortOrder = 0,
    ) {
    }

    public function id(): string { return $this->id; }
    public function name(): string { return $this->name; }
    public function description(): ?string { return $this->description; }
    public function photoPath(): ?string { return $this->photoPath; }
    public function isActive(): bool { return $this->isActive; }
    public function sortOrder(): int { return $this->sortOrder; }

    public function update(
        string $name,
        ?string $description = null,
        ?string $photoPath = null,
        bool $isActive = true,
        int $sortOrder = 0,
    ): void {
        $this->name = $name;
        $this->description = $description;
        $this->photoPath = $photoPath ?? $this->photoPath;
        $this->isActive = $isActive;
        $this->sortOrder = $sortOrder;
    }
}
