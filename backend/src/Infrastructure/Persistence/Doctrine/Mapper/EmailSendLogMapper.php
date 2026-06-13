<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Mapper;

use App\Domain\Notification\Entity\EmailSendLog as DomainEmailSendLog;
use App\Domain\Notification\ValueObject\EmailStatus;
use App\Entity\EmailSendLog as OrmEmailSendLog;

final class EmailSendLogMapper
{
    public function toDomain(OrmEmailSendLog $orm): DomainEmailSendLog
    {
        return new DomainEmailSendLog(
            id: $orm->getId(),
            recipientEmail: $orm->getRecipientEmail(),
            recipientName: $orm->getRecipientName(),
            bibNumber: $orm->getBibNumber(),
            status: new EmailStatus($orm->getStatus()),
            raceEditionId: $orm->getRaceEditionId(),
            errorMessage: $orm->getErrorMessage(),
            sentAt: $orm->getSentAt(),
            sentBy: $orm->getSentBy(),
            createdAt: $orm->getCreatedAt(),
            updatedAt: $orm->getUpdatedAt(),
        );
    }

    public function toOrm(DomainEmailSendLog $domain, ?OrmEmailSendLog $orm = null): OrmEmailSendLog
    {
        $target = $orm ?? new OrmEmailSendLog();
        $target->setId($domain->id());
        $target->setRecipientEmail($domain->recipientEmail());
        $target->setRecipientName($domain->recipientName());
        $target->setBibNumber($domain->bibNumber());
        $target->setRaceEditionId($domain->raceEditionId());
        $target->setStatus($domain->status()->value());
        $target->setErrorMessage($domain->errorMessage());
        $target->setSentAt($domain->sentAt());
        $target->setSentBy($domain->sentBy());
        $target->setCreatedAt($domain->createdAt() ?? new \DateTimeImmutable());
        $target->setUpdatedAt($domain->updatedAt() ?? new \DateTimeImmutable());

        return $target;
    }
}
