<?php

declare(strict_types=1);

namespace App\Domain\Race\Entity;

use App\Domain\Race\ValueObject\EditionYear;
use App\Domain\Race\ValueObject\RaceEditionId;
use DateTimeImmutable;

final class RaceEdition
{
    /** @var Category[] */
    private array $categories = [];

    public function __construct(
        private RaceEditionId $id,
        private EditionYear $year,
        private string $name,
        private string $description,
        private DateTimeImmutable $date,
        private string $location,
        private bool $isActive = true,
        private ?string $posterUrl = null,
        private ?string $registrationUrl = null,
        private ?string $shirtUrl = null,
        private ?string $inscriptionInfo = null,
        private ?string $solidarityCause = null,
        private ?string $solidarityUrl = null,
    ) {
    }

    public function id(): RaceEditionId
    {
        return $this->id;
    }

    public function year(): EditionYear
    {
        return $this->year;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function date(): DateTimeImmutable
    {
        return $this->date;
    }

    public function location(): string
    {
        return $this->location;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function posterUrl(): ?string
    {
        return $this->posterUrl;
    }

    public function registrationUrl(): ?string
    {
        return $this->registrationUrl;
    }

    public function setPosterUrl(?string $posterUrl): void
    {
        $this->posterUrl = $posterUrl;
    }

    public function setRegistrationUrl(?string $registrationUrl): void
    {
        $this->registrationUrl = $registrationUrl;
    }

    public function inscriptionInfo(): ?string
    {
        return $this->inscriptionInfo;
    }

    public function setInscriptionInfo(?string $inscriptionInfo): void
    {
        $this->inscriptionInfo = $inscriptionInfo;
    }

    public function solidarityCause(): ?string
    {
        return $this->solidarityCause;
    }

    public function setSolidarityCause(?string $solidarityCause): void
    {
        $this->solidarityCause = $solidarityCause;
    }

    public function solidarityUrl(): ?string
    {
        return $this->solidarityUrl;
    }

    public function setSolidarityUrl(?string $solidarityUrl): void
    {
        $this->solidarityUrl = $solidarityUrl;
    }

    public function shirtUrl(): ?string
    {
        return $this->shirtUrl;
    }

    public function setShirtUrl(?string $shirtUrl): void
    {
        $this->shirtUrl = $shirtUrl;
    }

    public function activate(): void
    {
        $this->isActive = true;
    }

    public function deactivate(): void
    {
        $this->isActive = false;
    }

    public function setYear(EditionYear $year): void
    {
        $this->year = $year;
    }

    public function update(string $name, string $description, DateTimeImmutable $date, string $location, ?string $shirtUrl = null): void
    {
        $this->name = $name;
        $this->description = $description;
        $this->date = $date;
        $this->location = $location;
        if ($shirtUrl !== null) {
            $this->shirtUrl = $shirtUrl;
        }
    }

    public function addCategory(Category $category): void
    {
        $this->categories[$category->id()] = $category;
    }

    public function removeCategory(string $categoryId): void
    {
        unset($this->categories[$categoryId]);
    }

    /** @return Category[] */
    public function categories(): array
    {
        return array_values($this->categories);
    }
}
