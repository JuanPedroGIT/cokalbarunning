<?php

declare(strict_types=1);

namespace App\Domain\Race\Repository;

use App\Domain\Race\Entity\RaceEdition;
use App\Domain\Race\ValueObject\EditionYear;
use App\Domain\Race\ValueObject\RaceEditionId;

interface RaceEditionRepositoryInterface
{
    public function save(RaceEdition $raceEdition): void;

    public function remove(RaceEdition $raceEdition): void;

    public function findById(RaceEditionId $id): ?RaceEdition;

    public function findByYear(EditionYear $year): ?RaceEdition;

    public function findActive(): ?RaceEdition;

    /** @return RaceEdition[] */
    public function findAllOrdered(): array;
}
