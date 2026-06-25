<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Mapper;

use App\Domain\Registration\Entity\Runner as DomainRunner;
use App\Entity\Runner as OrmRunner;

final class RunnerMapper
{
    public function toDomain(OrmRunner $orm): DomainRunner
    {
        return new DomainRunner(
            id: $orm->getId(),
            firstName: $orm->getFirstName(),
            lastName: $orm->getLastName(),
            email: $orm->getEmail(),
            raceEditionId: $orm->getRaceEditionId(),
            bibNumber: $orm->getBibNumber(),
            club: $orm->getClub(),
            birthDate: $orm->getBirthDate(),
            gender: $orm->getGender(),
            category: $orm->getCategory(),
        );
    }

    public function toOrm(DomainRunner $domain, ?OrmRunner $orm = null): OrmRunner
    {
        $target = $orm ?? new OrmRunner();
        $target->setId($domain->id());
        $target->setFirstName($domain->firstName());
        $target->setLastName($domain->lastName());
        $target->setEmail($domain->email());
        $target->setRaceEditionId($domain->raceEditionId());
        $target->setBibNumber($domain->bibNumber());
        $target->setClub($domain->club());
        $target->setBirthDate($domain->birthDate());
        $target->setGender($domain->gender());
        $target->setCategory($domain->category());

        return $target;
    }
}
