<?php

declare(strict_types=1);

namespace App\Domain\Club\Entity;

final class Sponsor
{
    public function __construct(
        private string $id,
        private string $name,
        private ?string $logoUrl = null,
        private ?string $website = null,
        private string $tier = 'bronze',
        private bool $isActive = true,
        private int $sortOrder = 0,
        private ?string $message = null,
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

    public function logoUrl(): ?string
    {
        return $this->logoUrl;
    }

    public function website(): ?string
    {
        return $this->website;
    }

    public function tier(): string
    {
        return $this->tier;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function sortOrder(): int
    {
        return $this->sortOrder;
    }

    public function message(): ?string
    {
        return $this->message;
    }

    public function update(string $name, ?string $logoUrl, ?string $website, string $tier, int $sortOrder, ?string $message = null): void
    {
        $this->name = $name;
        $this->logoUrl = $logoUrl;
        $this->website = $website;
        $this->tier = $tier;
        $this->sortOrder = $sortOrder;
        $this->message = $message;
    }

    public function activate(): void
    {
        $this->isActive = true;
    }

    public function deactivate(): void
    {
        $this->isActive = false;
    }
}
