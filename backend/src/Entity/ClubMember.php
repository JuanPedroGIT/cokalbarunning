<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ClubMemberRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'club_members')]
class ClubMember
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $bio = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $photoPath = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isActive = true;

    #[ORM\Column(type: 'integer')]
    private int $sortOrder = 0;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $createdBy = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $updatedBy = null;

    public function getId(): string { return $this->id; }
    public function setId(string $id): static { $this->id = $id; return $this; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }
    public function getBio(): ?string { return $this->bio; }
    public function setBio(?string $bio): static { $this->bio = $bio; return $this; }
    public function getPhotoPath(): ?string { return $this->photoPath; }
    public function setPhotoPath(?string $photoPath): static { $this->photoPath = $photoPath; return $this; }
    public function isActive(): bool { return $this->isActive; }
    public function setIsActive(bool $isActive): static { $this->isActive = $isActive; return $this; }
    public function getSortOrder(): int { return $this->sortOrder; }
    public function setSortOrder(int $sortOrder): static { $this->sortOrder = $sortOrder; return $this; }
    public function getCreatedBy(): ?string { return $this->createdBy; }
    public function setCreatedBy(?string $v): static { $this->createdBy = $v; return $this; }
    public function getUpdatedBy(): ?string { return $this->updatedBy; }
    public function setUpdatedBy(?string $v): static { $this->updatedBy = $v; return $this; }
}
