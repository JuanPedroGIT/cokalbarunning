<?php

declare(strict_types=1);

namespace App\Domain\Media\Repository;

use App\Domain\Media\Entity\Photo;

interface PhotoRepositoryInterface
{
    public function save(Photo $photo): void;

    public function remove(Photo $photo): void;

    public function findById(string $id): ?Photo;

    /** @return Photo[] */
    public function findFeatured(): array;

    /** @return Photo[] */
    public function findAll(): array;

    /** @return Photo[] */
    public function findByEditionId(string $editionId): array;
}
