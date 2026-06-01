<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\PhotoRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PhotoRepository::class)]
#[ORM\Table(name: 'photos')]
class Photo
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(length: 255)]
    private string $originalPath;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $thumbPath = null;

    #[ORM\ManyToOne(targetEntity: RaceEdition::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?RaceEdition $raceEdition = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $altText = null;

    #[ORM\Column]
    private bool $isFeatured = false;

    #[ORM\Column]
    private int $sortOrder = 0;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): static
    {
        $this->id = $id;
        return $this;
    }

    public function getOriginalPath(): string
    {
        return $this->originalPath;
    }

    public function setOriginalPath(string $originalPath): static
    {
        $this->originalPath = $originalPath;
        return $this;
    }

    public function getThumbPath(): ?string
    {
        return $this->thumbPath;
    }

    public function setThumbPath(?string $thumbPath): static
    {
        $this->thumbPath = $thumbPath;
        return $this;
    }

    public function getRaceEdition(): ?RaceEdition
    {
        return $this->raceEdition;
    }

    public function setRaceEdition(?RaceEdition $raceEdition): static
    {
        $this->raceEdition = $raceEdition;
        return $this;
    }

    public function getAltText(): ?string
    {
        return $this->altText;
    }

    public function setAltText(?string $altText): static
    {
        $this->altText = $altText;
        return $this;
    }

    public function isFeatured(): bool
    {
        return $this->isFeatured;
    }

    public function setIsFeatured(bool $isFeatured): static
    {
        $this->isFeatured = $isFeatured;
        return $this;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): static
    {
        $this->sortOrder = $sortOrder;
        return $this;
    }
}
