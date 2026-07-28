<?php

declare(strict_types=1);

namespace App\Domain\Notification\Repository;

use App\Domain\Notification\Entity\EmailConfig;

interface EmailConfigRepositoryInterface
{
    public function save(EmailConfig $config): void;

    public function remove(EmailConfig $config): void;

    public function findById(string $id): ?EmailConfig;

    public function findByRaceEditionIdAndType(string $raceEditionId, string $type): ?EmailConfig;

    /** @return EmailConfig[] */
    public function findByRaceEditionId(string $raceEditionId): array;

    /** @return EmailConfig[] */
    public function findAll(): array;
}
