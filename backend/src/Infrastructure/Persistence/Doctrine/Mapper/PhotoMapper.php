<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Mapper;

use App\Domain\Media\Entity\Photo as DomainPhoto;
use App\Domain\Race\ValueObject\RaceEditionId;
use App\Entity\Photo as OrmPhoto;

final class PhotoMapper
{
    public function toDomain(OrmPhoto $orm): DomainPhoto
    {
        return new DomainPhoto(
            id: $orm->getId(),
            originalPath: $orm->getOriginalPath(),
            thumbPath: $orm->getThumbPath(),
            raceEditionId: $orm->getRaceEdition()?->getId()
                ? RaceEditionId::fromString($orm->getRaceEdition()->getId())
                : null,
            altText: $orm->getAltText(),
            isFeatured: $orm->isFeatured(),
            sortOrder: $orm->getSortOrder(),
        );
    }

    public function toOrm(DomainPhoto $domain, ?OrmPhoto $orm = null): OrmPhoto
    {
        $target = $orm ?? new OrmPhoto();
        $target->setId($domain->id());
        $target->setOriginalPath($domain->originalPath());
        $target->setThumbPath($domain->thumbPath());
        $target->setAltText($domain->altText());
        $target->setIsFeatured($domain->isFeatured());
        $target->setSortOrder($domain->sortOrder());

        return $target;
    }
}
