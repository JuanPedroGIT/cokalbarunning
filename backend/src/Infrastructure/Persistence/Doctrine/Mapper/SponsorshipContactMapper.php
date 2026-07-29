<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Mapper;

use App\Domain\Sponsorship\Contact\Entity\SponsorshipContact as DomainContact;
use App\Entity\SponsorshipContact as OrmContact;

final class SponsorshipContactMapper
{
    public function toDomain(OrmContact $orm): DomainContact
    {
        return new DomainContact(
            id: $orm->getId(),
            name: $orm->getName(),
            company: $orm->getCompany(),
            email: $orm->getEmail(),
            phone: $orm->getPhone(),
            message: $orm->getMessage(),
            createdAt: $orm->getCreatedAt(),
        );
    }

    public function toOrm(DomainContact $domain, ?OrmContact $orm = null): OrmContact
    {
        $target = $orm ?? new OrmContact();
        $target->setId($domain->id());
        $target->setName($domain->name());
        $target->setCompany($domain->company());
        $target->setEmail($domain->email());
        $target->setPhone($domain->phone());
        $target->setMessage($domain->message());
        $target->setCreatedAt($domain->createdAt());

        return $target;
    }
}
