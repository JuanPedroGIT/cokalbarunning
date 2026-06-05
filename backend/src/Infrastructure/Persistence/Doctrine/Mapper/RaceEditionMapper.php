<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Mapper;

use App\Domain\Race\Entity\RaceEdition as DomainRaceEdition;
use App\Domain\Race\ValueObject\EditionYear;
use App\Domain\Race\ValueObject\RaceEditionId;
use App\Entity\RaceEdition as OrmRaceEdition;

final class RaceEditionMapper
{
    public function __construct(private CategoryMapper $categoryMapper)
    {
    }

    public function toDomain(OrmRaceEdition $orm): DomainRaceEdition
    {
        $edition = new DomainRaceEdition(
            id: RaceEditionId::fromString($orm->getId()),
            year: EditionYear::fromInt($orm->getYear()),
            name: $orm->getName(),
            description: $orm->getDescription(),
            date: $orm->getDate(),
            location: $orm->getLocation(),
            isActive: $orm->isActive(),
            posterUrl: $orm->getPosterUrl(),
            registrationUrl: $orm->getRegistrationUrl(),
            shirtUrl: $orm->getShirtUrl(),
            trophyUrl: $orm->getTrophyUrl(),
            inscriptionInfo: $orm->getInscriptionInfo(),
            solidarityCause: $orm->getSolidarityCause(),
            solidarityUrl: $orm->getSolidarityUrl(),
        );

        foreach ($orm->getCategories() as $category) {
            $edition->addCategory($this->categoryMapper->toDomain($category));
        }

        return $edition;
    }

    public function toOrm(DomainRaceEdition $domain, ?OrmRaceEdition $orm = null): OrmRaceEdition
    {
        $target = $orm ?? new OrmRaceEdition();
        $target->setId($domain->id()->value());
        $target->setYear($domain->year()->value());
        $target->setName($domain->name());
        $target->setDescription($domain->description());
        $target->setDate($domain->date());
        $target->setLocation($domain->location());
        $target->setIsActive($domain->isActive());
        $target->setPosterUrl($domain->posterUrl());
        $target->setRegistrationUrl($domain->registrationUrl());
        $target->setShirtUrl($domain->shirtUrl());
        $target->setTrophyUrl($domain->trophyUrl());
        $target->setInscriptionInfo($domain->inscriptionInfo());
        $target->setSolidarityCause($domain->solidarityCause());
        $target->setSolidarityUrl($domain->solidarityUrl());

        return $target;
    }
}
