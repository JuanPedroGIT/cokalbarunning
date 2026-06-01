<?php

declare(strict_types=1);

namespace App\Domain\Results\Repository;

use App\Domain\Race\ValueObject\RaceEditionId;
use App\Domain\Results\Entity\Result;

interface ResultRepositoryInterface
{
    public function save(Result $result): void;

    public function saveBulk(array $results): void;

    public function remove(Result $result): void;

    public function findById(string $id): ?Result;

    /** @return Result[] */
    public function findByRaceEdition(RaceEditionId $raceEditionId): array;

    /** @return Result[] */
    public function findByRaceEditionAndCategory(RaceEditionId $raceEditionId, string $categoryId): array;

    /** @return Result[] */
    public function findByRaceEditionAndGender(RaceEditionId $raceEditionId, string $gender): array;

    public function clearPositionsForEdition(RaceEditionId $raceEditionId): void;
}
