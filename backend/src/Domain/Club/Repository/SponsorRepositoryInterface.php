<?php

declare(strict_types=1);

namespace App\Domain\Club\Repository;

use App\Domain\Club\Entity\Sponsor;

interface SponsorRepositoryInterface
{
    public function save(Sponsor $sponsor): void;

    public function remove(Sponsor $sponsor): void;

    public function findById(string $id): ?Sponsor;

    /** @return Sponsor[] */
    public function findAllActive(): array;

    /** @return Sponsor[] */
    public function findAll(): array;
}
