<?php

declare(strict_types=1);

namespace App\Application\Race\Response;

use App\Domain\Media\Port\StoragePort;
use App\Domain\Race\Entity\RaceEdition;

final readonly class RaceEditionResponseDto
{
    /**
     * @param CategoryResponseDto[] $categories
     */
    public function __construct(
        public string $id,
        public int $year,
        public string $name,
        public string $date,
        public string $location,
        public bool $isActive,
        public ?string $posterUrl,
        public ?string $registrationUrl,
        public ?string $shirtUrl = null,
        public ?string $description = null,
        public ?string $resultsUrl = null,
        public ?string $inscriptionInfo = null,
        public ?string $solidarityCause = null,
        public ?string $solidarityUrl = null,
        public array $categories = [],
    ) {
    }

    private static function buildUrl(?string $path, StoragePort $storage): ?string
    {
        if ($path === null) return null;
        if (str_starts_with($path, 'http')) return $path;
        return $storage->url($path);
    }

    public static function fromDomain(RaceEdition $edition, StoragePort $storage): self
    {
        return new self(
            id: $edition->id()->value(),
            year: $edition->year()->value(),
            name: $edition->name(),
            date: $edition->date()->format('Y-m-d'),
            location: $edition->location(),
            isActive: $edition->isActive(),
            posterUrl: self::buildUrl($edition->posterUrl(), $storage),
            registrationUrl: $edition->registrationUrl(),
            shirtUrl: self::buildUrl($edition->shirtUrl(), $storage),
            description: $edition->description(),
            inscriptionInfo: $edition->inscriptionInfo(),
            solidarityCause: $edition->solidarityCause(),
            solidarityUrl: $edition->solidarityUrl(),
        );
    }

    public static function fromDomainDetailed(RaceEdition $edition, StoragePort $storage): self
    {
        return new self(
            id: $edition->id()->value(),
            year: $edition->year()->value(),
            name: $edition->name(),
            date: $edition->date()->format('Y-m-d'),
            location: $edition->location(),
            isActive: $edition->isActive(),
            posterUrl: self::buildUrl($edition->posterUrl(), $storage),
            registrationUrl: $edition->registrationUrl(),
            shirtUrl: self::buildUrl($edition->shirtUrl(), $storage),
            description: $edition->description(),
            inscriptionInfo: $edition->inscriptionInfo(),
            solidarityCause: $edition->solidarityCause(),
            solidarityUrl: $edition->solidarityUrl(),
            categories: CategoryResponseDto::fromDomainList($edition->categories()),
        );
    }

    /**
     * @param RaceEdition[] $editions
     * @return self[]
     */
    public static function fromDomainList(array $editions, StoragePort $storage): array
    {
        return array_map(fn (RaceEdition $e) => self::fromDomain($e, $storage), $editions);
    }

    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'year' => $this->year,
            'name' => $this->name,
            'date' => $this->date,
            'location' => $this->location,
            'isActive' => $this->isActive,
            'posterUrl' => $this->posterUrl,
            'registrationUrl' => $this->registrationUrl,
            'shirtUrl' => $this->shirtUrl,
        ];

        if ($this->description !== null) {
            $data['description'] = $this->description;
        }
        if ($this->resultsUrl !== null) {
            $data['resultsUrl'] = $this->resultsUrl;
        }
        if ($this->inscriptionInfo !== null) {
            $data['inscriptionInfo'] = $this->inscriptionInfo;
        }
        if ($this->solidarityCause !== null) {
            $data['solidarityCause'] = $this->solidarityCause;
        }
        if ($this->solidarityUrl !== null) {
            $data['solidarityUrl'] = $this->solidarityUrl;
        }

        if ($this->categories !== []) {
            $data['categories'] = CategoryResponseDto::listToArray($this->categories);
        }

        return $data;
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
