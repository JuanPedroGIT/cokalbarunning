<?php

declare(strict_types=1);

namespace App\Application\Media\Response;

use App\Domain\Media\Entity\Photo;

final readonly class PhotoResponseDto
{
    public function __construct(
        public string $id,
        public string $originalPath,
        public string $originalUrl,
        public ?string $thumbPath,
        public ?string $thumbUrl,
        public ?string $altText,
        public bool $isFeatured,
        public int $sortOrder,
        public ?string $raceEditionId,
    ) {
    }

    public static function fromDomain(Photo $photo, callable $urlBuilder): self
    {
        return new self(
            id: $photo->id(),
            originalPath: $photo->originalPath(),
            originalUrl: $urlBuilder($photo->originalPath()),
            thumbPath: $photo->thumbPath(),
            thumbUrl: $photo->thumbPath() !== null ? $urlBuilder($photo->thumbPath()) : null,
            altText: $photo->altText(),
            isFeatured: $photo->isFeatured(),
            sortOrder: $photo->sortOrder(),
            raceEditionId: $photo->raceEditionId()?->value(),
        );
    }

    /**
     * @param Photo[] $photos
     * @return self[]
     */
    public static function fromDomainList(array $photos, callable $urlBuilder): array
    {
        return array_map(
            fn (Photo $p) => self::fromDomain($p, $urlBuilder),
            $photos
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'originalPath' => $this->originalPath,
            'originalUrl' => $this->originalUrl,
            'thumbPath' => $this->thumbPath,
            'thumbUrl' => $this->thumbUrl,
            'altText' => $this->altText,
            'isFeatured' => $this->isFeatured,
            'sortOrder' => $this->sortOrder,
            'raceEditionId' => $this->raceEditionId,
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
