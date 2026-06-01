<?php

declare(strict_types=1);

namespace App\Domain\Registration\Repository;

use App\Domain\Registration\Entity\Runner;

interface RunnerRepositoryInterface
{
    public function save(Runner $runner): void;

    public function findById(string $id): ?Runner;

    public function findByEmail(string $email): ?Runner;
}
