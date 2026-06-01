<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Mapper;

use App\Domain\Race\Entity\Category as DomainCategory;
use App\Domain\Race\ValueObject\Distance;
use App\Entity\Category as OrmCategory;

final class CategoryMapper
{
    public function toDomain(OrmCategory $orm): DomainCategory
    {
        return new DomainCategory(
            id: $orm->getId(),
            name: $orm->getName(),
            minAge: $orm->getMinAge(),
            maxAge: $orm->getMaxAge(),
            distance: Distance::fromKilometers($orm->getDistanceKm()),
            gender: $orm->getGender(),
        );
    }

    public function toOrm(DomainCategory $domain, ?OrmCategory $orm = null): OrmCategory
    {
        $target = $orm ?? new OrmCategory();
        $target->setId($domain->id());
        $target->setName($domain->name());
        $target->setMinAge($domain->minAge());
        $target->setMaxAge($domain->maxAge());
        $target->setDistanceKm($domain->distance()->kilometers());
        $target->setGender($domain->gender());

        return $target;
    }
}
