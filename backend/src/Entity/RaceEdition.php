<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'race_editions')]
class RaceEdition
{
    #[ORM\Id]
    #[ORM\Column(type: Types::GUID)]
    private string $id;

    #[ORM\Column(type: Types::SMALLINT)]
    private int $year;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(type: Types::TEXT)]
    private string $description;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $date;

    #[ORM\Column(length: 255)]
    private string $location;

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column]
    private bool $showBibSearch = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $posterUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $registrationUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $shirtUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $trophyUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $inscriptionInfo = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $createdBy = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $updatedBy = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $solidarityCause = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $solidarityUrl = null;

    #[ORM\OneToMany(targetEntity: Category::class, mappedBy: 'raceEdition', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $categories;

    public function __construct()
    {
        $this->categories = new ArrayCollection();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): static
    {
        $this->id = $id;
        return $this;
    }

    public function getYear(): int
    {
        return $this->year;
    }

    public function setYear(int $year): static
    {
        $this->year = $year;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getDate(): \DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(\DateTimeImmutable $date): static
    {
        $this->date = $date;
        return $this;
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    public function setLocation(string $location): static
    {
        $this->location = $location;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function isShowBibSearch(): bool
    {
        return $this->showBibSearch;
    }

    public function setShowBibSearch(bool $showBibSearch): static
    {
        $this->showBibSearch = $showBibSearch;
        return $this;
    }

    public function getPosterUrl(): ?string
    {
        return $this->posterUrl;
    }

    public function setPosterUrl(?string $posterUrl): static
    {
        $this->posterUrl = $posterUrl;
        return $this;
    }

    public function getRegistrationUrl(): ?string
    {
        return $this->registrationUrl;
    }

    public function setRegistrationUrl(?string $registrationUrl): static
    {
        $this->registrationUrl = $registrationUrl;
        return $this;
    }

    public function getShirtUrl(): ?string
    {
        return $this->shirtUrl;
    }

    public function setShirtUrl(?string $shirtUrl): static
    {
        $this->shirtUrl = $shirtUrl;
        return $this;
    }

    public function getTrophyUrl(): ?string
    {
        return $this->trophyUrl;
    }

    public function setTrophyUrl(?string $trophyUrl): static
    {
        $this->trophyUrl = $trophyUrl;
        return $this;
    }

    public function getInscriptionInfo(): ?string
    {
        return $this->inscriptionInfo;
    }

    public function setInscriptionInfo(?string $inscriptionInfo): static
    {
        $this->inscriptionInfo = $inscriptionInfo;
        return $this;
    }

    public function getSolidarityCause(): ?string { return $this->solidarityCause; }
    public function setSolidarityCause(?string $v): static { $this->solidarityCause = $v; return $this; }
    public function getSolidarityUrl(): ?string { return $this->solidarityUrl; }
    public function setSolidarityUrl(?string $v): static { $this->solidarityUrl = $v; return $this; }
    public function getCreatedBy(): ?string { return $this->createdBy; }
    public function setCreatedBy(?string $v): static { $this->createdBy = $v; return $this; }
    public function getUpdatedBy(): ?string { return $this->updatedBy; }
    public function setUpdatedBy(?string $v): static { $this->updatedBy = $v; return $this; }

    /** @return Collection<int, Category> */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function addCategory(Category $category): static
    {
        if (!$this->categories->contains($category)) {
            $this->categories->add($category);
            $category->setRaceEdition($this);
        }
        return $this;
    }

    public function removeCategory(Category $category): static
    {
        if ($this->categories->removeElement($category)) {
            if ($category->getRaceEdition() === $this) {
                $category->setRaceEdition(null);
            }
        }
        return $this;
    }
}
