<?php

declare(strict_types=1);

namespace App\Domain\Club\Repository;

use App\Domain\Club\Entity\ClubMember;

interface ClubMemberRepositoryInterface
{
    public function save(ClubMember $member): void;
    public function remove(ClubMember $member): void;
    public function findById(string $id): ?ClubMember;

    /** @return ClubMember[] */
    public function findAllActive(): array;

    /** @return ClubMember[] */
    public function findAll(): array;
    public function findByUserId(string $userId): ?ClubMember;
}
