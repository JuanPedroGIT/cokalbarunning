<?php

declare(strict_types=1);

namespace App\Application\Club\Response;

use App\Domain\Club\Entity\ClubMember;
use App\Domain\Media\Port\StoragePort;

final readonly class ClubMemberResponseDto
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $description,
        public ?string $bio,
        public ?string $photoUrl,
        public bool $isActive,
        public int $sortOrder,
    ) {
    }

    public static function fromDomain(ClubMember $member, StoragePort $storage): self
    {
        return new self(
            id: $member->id(),
            name: $member->name(),
            description: $member->description(),
            bio: $member->bio(),
            photoUrl: $member->photoPath() !== null ? $storage->url($member->photoPath()) : null,
            isActive: $member->isActive(),
            sortOrder: $member->sortOrder(),
        );
    }

    /**
     * @param ClubMember[] $members
     * @return self[]
     */
    public static function fromDomainList(array $members, StoragePort $storage): array
    {
        return array_map(fn (ClubMember $m) => self::fromDomain($m, $storage), $members);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'bio' => $this->bio,
            'photoUrl' => $this->photoUrl,
            'isActive' => $this->isActive,
            'sortOrder' => $this->sortOrder,
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
