<?php

declare(strict_types=1);

namespace App\Application\Race\Response;

use App\Domain\Race\Entity\RaceDocument;

final readonly class RaceDocumentResponseDto
{
    public function __construct(
        public string $id,
        public string $name,
        public string $type,
        public string $filePath,
        public string $publicUrl,
        public ?string $editionId,
        public string $createdAt,
    ) {
    }

    public static function fromDomain(RaceDocument $document, string $publicUrl): self
    {
        return new self(
            id: $document->id(),
            name: $document->name(),
            type: $document->type()->value(),
            filePath: $document->filePath(),
            publicUrl: $publicUrl,
            editionId: $document->editionId(),
            createdAt: $document->createdAt()->format('Y-m-d H:i:s'),
        );
    }

    /**
     * @param RaceDocument[] $documents
     * @return self[]
     */
    public static function fromDomainList(array $documents, callable $urlBuilder): array
    {
        return array_map(
            fn (RaceDocument $d) => self::fromDomain($d, $urlBuilder($d->filePath())),
            $documents
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'filePath' => $this->filePath,
            'publicUrl' => $this->publicUrl,
            'editionId' => $this->editionId,
            'createdAt' => $this->createdAt,
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
