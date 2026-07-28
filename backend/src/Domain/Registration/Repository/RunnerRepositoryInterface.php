<?php

declare(strict_types=1);

namespace App\Domain\Registration\Repository;

use App\Domain\Registration\Entity\Runner;

interface RunnerRepositoryInterface
{
    public function save(Runner $runner): void;

    public function remove(Runner $runner): void;

    public function findById(string $id): ?Runner;

    public function findByBibNumberAndEditionId(string $bibNumber, string $raceEditionId): ?Runner;

    public function findByEmailAndEditionId(string $email, string $raceEditionId): ?Runner;

    /** @return Runner[] */
    public function findByEditionId(string $raceEditionId): array;

    /** @return Runner[] */
    public function findByEditionIdAndBibRange(string $raceEditionId, ?string $bibFrom, ?string $bibTo): array;

    /** @return Runner[] */
    public function searchByEditionId(string $raceEditionId, ?string $name, ?string $bib, int $maxResults): array;
}
