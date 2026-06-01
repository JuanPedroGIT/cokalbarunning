<?php

declare(strict_types=1);

namespace App\Domain\Race\Repository;

use App\Domain\Race\Entity\RaceDocument;

interface RaceDocumentRepositoryInterface
{
    public function save(RaceDocument $document): void;

    public function remove(RaceDocument $document): void;

    public function findById(string $id): ?RaceDocument;

    /** @return RaceDocument[] */
    public function findByEditionId(string $editionId): array;

    /** @return RaceDocument[] */
    public function findGeneral(): array;

    /** @return RaceDocument[] */
    public function findAll(): array;
}
