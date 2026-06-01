<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Mapper;

use App\Domain\Club\Entity\ClubMember as DomainMember;
use App\Entity\ClubMember as OrmMember;

final class ClubMemberMapper
{
    public function toDomain(OrmMember $orm): DomainMember
    {
        return new DomainMember(
            id: $orm->getId(),
            name: $orm->getName(),
            description: $orm->getDescription(),
            photoPath: $orm->getPhotoPath(),
            isActive: $orm->isActive(),
            sortOrder: $orm->getSortOrder(),
        );
    }

    public function toOrm(DomainMember $domain, ?OrmMember $orm = null): OrmMember
    {
        $target = $orm ?? new OrmMember();
        $target->setId($domain->id());
        $target->setName($domain->name());
        $target->setDescription($domain->description());
        $target->setPhotoPath($domain->photoPath());
        $target->setIsActive($domain->isActive());
        $target->setSortOrder($domain->sortOrder());
        return $target;
    }
}
