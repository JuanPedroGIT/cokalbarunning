<?php

declare(strict_types=1);

namespace App\Application\Club\Response;

use App\Domain\Club\Entity\Sponsor;
use App\Domain\Media\Port\StoragePort;

final readonly class SponsorResponseDto
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $logoUrl,
        public ?string $website,
        public string $tier,
        public bool $isActive,
        public int $sortOrder,
        public ?string $message = null,
    ) {
    }

    private static function buildUrl(?string $path, StoragePort $storage): ?string
    {
        if ($path === null) return null;
        if (str_starts_with($path, 'http')) return $path;
        return $storage->url($path);
    }

    public static function fromDomain(Sponsor $sponsor, StoragePort $storage): self
    {
        return new self(
            id: $sponsor->id(),
            name: $sponsor->name(),
            logoUrl: self::buildUrl($sponsor->logoUrl(), $storage),
            website: $sponsor->website(),
            tier: $sponsor->tier(),
            isActive: $sponsor->isActive(),
            sortOrder: $sponsor->sortOrder(),
            message: $sponsor->message(),
        );
    }

    /**
     * @param Sponsor[] $sponsors
     * @return self[]
     */
    public static function fromDomainList(array $sponsors, StoragePort $storage): array
    {
        return array_map(fn (Sponsor $s) => self::fromDomain($s, $storage), $sponsors);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'logoUrl' => $this->logoUrl,
            'website' => $this->website,
            'tier' => $this->tier,
            'isActive' => $this->isActive,
            'sortOrder' => $this->sortOrder,
            'message' => $this->message,
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
