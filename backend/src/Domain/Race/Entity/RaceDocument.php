<?php

declare(strict_types=1);

namespace App\Domain\Race\Entity;

use App\Domain\Race\ValueObject\DocumentType;
use DateTimeImmutable;

final class RaceDocument
{
    public function __construct(
        private string $id,
        private string $name,
        private DocumentType $type,
        private string $filePath,
        private DateTimeImmutable $createdAt,
        private ?string $editionId = null,
    ) {
    }

    public static function create(
        string $id,
        string $name,
        string $type,
        string $filePath,
        ?string $editionId = null,
        ?DateTimeImmutable $createdAt = null,
    ): self {
        return new self(
            id: $id,
            name: $name,
            type: new DocumentType($type),
            filePath: $filePath,
            editionId: $editionId,
            createdAt: $createdAt ?? new DateTimeImmutable(),
        );
    }

    public function id(): string
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function type(): DocumentType
    {
        return $this->type;
    }

    public function filePath(): string
    {
        return $this->filePath;
    }

    public function editionId(): ?string
    {
        return $this->editionId;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function update(string $name, string $type, ?string $editionId = null): void
    {
        $this->name = $name;
        $this->type = new DocumentType($type);
        if ($editionId !== null || $this->editionId !== null) {
            $this->editionId = $editionId;
        }
    }

    public function setFilePath(string $filePath): void
    {
        $this->filePath = $filePath;
    }
}
