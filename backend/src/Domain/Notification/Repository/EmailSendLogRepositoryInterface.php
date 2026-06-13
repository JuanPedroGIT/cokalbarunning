<?php

declare(strict_types=1);

namespace App\Domain\Notification\Repository;

use App\Domain\Notification\Entity\EmailSendLog;

interface EmailSendLogRepositoryInterface
{
    public function save(EmailSendLog $log): void;

    public function findById(string $id): ?EmailSendLog;

    /**
     * @return EmailSendLog[]
     */
    public function findByRaceEditionId(string $raceEditionId): array;

    public function findByEmailAndBibNumber(string $email, string $bibNumber): ?EmailSendLog;

    public function countSentByEmail(string $email): int;

    /**
     * @return EmailSendLog[]
     */
    public function findAll(): array;
}
