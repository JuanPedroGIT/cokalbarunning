<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Mapper;

use App\Domain\Race\Entity\RaceDocument as DomainRaceDocument;
use App\Entity\RaceDocument as OrmRaceDocument;

final class RaceDocumentMapper
{
    public function toDomain(OrmRaceDocument $orm): DomainRaceDocument
    {
        return DomainRaceDocument::create(
            id: $orm->getId(),
            name: $orm->getName(),
            type: $orm->getType(),
            filePath: $orm->getFilePath(),
            editionId: $orm->getEdition()?->getId(),
            createdAt: $orm->getCreatedAt(),
        );
    }

    public function toOrm(DomainRaceDocument $domain, ?OrmRaceDocument $orm = null): OrmRaceDocument
    {
        $target = $orm ?? new OrmRaceDocument();
        $target->setId($domain->id());
        $target->setName($domain->name());
        $target->setType($domain->type()->value());
        $target->setFilePath($domain->filePath());

        return $target;
    }
}
