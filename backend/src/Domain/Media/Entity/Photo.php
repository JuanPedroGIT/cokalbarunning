<?php

declare(strict_types=1);

namespace App\Domain\Media\Entity;

use App\Domain\Race\ValueObject\RaceEditionId;

final class Photo
{
    public function __construct(
        private string $id,
        private string $originalPath,
        private ?string $thumbPath = null,
        private ?RaceEditionId $raceEditionId = null,
        private ?string $altText = null,
        private bool $isFeatured = false,
        private int $sortOrder = 0,
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function originalPath(): string
    {
        return $this->originalPath;
    }

    public function thumbPath(): ?string
    {
        return $this->thumbPath;
    }

    public function raceEditionId(): ?RaceEditionId
    {
        return $this->raceEditionId;
    }

    public function altText(): ?string
    {
        return $this->altText;
    }

    public function isFeatured(): bool
    {
        return $this->isFeatured;
    }

    public function sortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setOriginalPath(string $originalPath): void
    {
        $this->originalPath = $originalPath;
    }

    public function setThumbPath(?string $thumbPath): void
    {
        $this->thumbPath = $thumbPath;
    }

    public function setAltText(?string $altText): void
    {
        $this->altText = $altText;
    }

    public function setFeatured(bool $isFeatured): void
    {
        $this->isFeatured = $isFeatured;
    }

    public function setSortOrder(int $sortOrder): void
    {
        $this->sortOrder = $sortOrder;
    }

    public function setRaceEditionId(?RaceEditionId $raceEditionId): void
    {
        $this->raceEditionId = $raceEditionId;
    }
}
