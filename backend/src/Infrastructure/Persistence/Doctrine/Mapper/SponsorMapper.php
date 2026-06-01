<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Mapper;

use App\Domain\Club\Entity\Sponsor as DomainSponsor;
use App\Entity\Sponsor as OrmSponsor;

final class SponsorMapper
{
    public function toDomain(OrmSponsor $orm): DomainSponsor
    {
        return new DomainSponsor(
            id: $orm->getId(),
            name: $orm->getName(),
            logoUrl: $orm->getLogoUrl(),
            website: $orm->getWebsite(),
            tier: $orm->getTier(),
            isActive: $orm->isActive(),
            sortOrder: $orm->getSortOrder(),
            message: $orm->getMessage(),
        );
    }

    public function toOrm(DomainSponsor $domain, ?OrmSponsor $orm = null): OrmSponsor
    {
        $target = $orm ?? new OrmSponsor();
        $target->setId($domain->id());
        $target->setName($domain->name());
        $target->setLogoUrl($domain->logoUrl());
        $target->setWebsite($domain->website());
        $target->setTier($domain->tier());
        $target->setIsActive($domain->isActive());
        $target->setSortOrder($domain->sortOrder());
        $target->setMessage($domain->message());

        return $target;
    }
}
