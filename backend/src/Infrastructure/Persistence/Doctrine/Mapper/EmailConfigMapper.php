<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Mapper;

use App\Domain\Notification\Entity\EmailConfig as DomainEmailConfig;
use App\Entity\EmailConfig as OrmEmailConfig;

final class EmailConfigMapper
{
    public function toDomain(OrmEmailConfig $orm): DomainEmailConfig
    {
        return new DomainEmailConfig(
            id: $orm->getId(),
            raceEditionId: $orm->getRaceEditionId(),
            type: $orm->getType(),
            subject: $orm->getSubject(),
            title: $orm->getTitle(),
            description: $orm->getDescription(),
            prize: $orm->getPrize(),
            drawDate: $orm->getDrawDate(),
            prizeImageUrl: $orm->getPrizeImageUrl(),
            createdAt: $orm->getCreatedAt(),
            updatedAt: $orm->getUpdatedAt(),
        );
    }

    public function toOrm(DomainEmailConfig $domain, ?OrmEmailConfig $orm = null): OrmEmailConfig
    {
        $target = $orm ?? new OrmEmailConfig();
        $target->setId($domain->id());
        $target->setRaceEditionId($domain->raceEditionId());
        $target->setType($domain->type());
        $target->setSubject($domain->subject());
        $target->setTitle($domain->title());
        $target->setDescription($domain->description());
        $target->setPrize($domain->prize());
        $target->setDrawDate($domain->drawDate());
        $target->setPrizeImageUrl($domain->prizeImageUrl());
        $target->setCreatedAt($domain->createdAt());
        $target->setUpdatedAt($domain->updatedAt());

        return $target;
    }
}
