<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Mapper;

use App\Domain\Notification\Entity\EmailSendLog as DomainEmailSendLog;
use App\Domain\Notification\ValueObject\EmailStatus;
use App\Domain\Notification\ValueObject\EmailType;
use App\Entity\EmailSendLog as OrmEmailSendLog;

final class EmailSendLogMapper
{
    public function toDomain(OrmEmailSendLog $orm): DomainEmailSendLog
    {
        return new DomainEmailSendLog(
            id: $orm->getId(),
            type: new EmailType($orm->getType()),
            recipientEmail: $orm->getRecipientEmail(),
            recipientName: $orm->getRecipientName(),
            reference: $orm->getReference(),
            status: new EmailStatus($orm->getStatus()),
            raceEditionId: $orm->getRaceEditionId(),
            errorMessage: $orm->getErrorMessage(),
            sentAt: $orm->getSentAt(),
            sentBy: $orm->getSentBy(),
            createdAt: $orm->getCreatedAt(),
            updatedAt: $orm->getUpdatedAt(),
            metadata: $orm->getMetadata() ?? [],
        );
    }

    public function toOrm(DomainEmailSendLog $domain, ?OrmEmailSendLog $orm = null): OrmEmailSendLog
    {
        $target = $orm ?? new OrmEmailSendLog();
        $target->setId($domain->id());
        $target->setType($domain->type()->value());
        $target->setRecipientEmail($domain->recipientEmail());
        $target->setRecipientName($domain->recipientName());
        $target->setReference($domain->reference());
        $target->setRaceEditionId($domain->raceEditionId());
        $target->setStatus($domain->status()->value());
        $target->setErrorMessage($domain->errorMessage());
        $target->setSentAt($domain->sentAt());
        $target->setSentBy($domain->sentBy());
        $target->setMetadata($domain->metadata() === [] ? null : $domain->metadata());
        $target->setCreatedAt($domain->createdAt() ?? new \DateTimeImmutable());
        $target->setUpdatedAt($domain->updatedAt() ?? new \DateTimeImmutable());

        return $target;
    }
}
