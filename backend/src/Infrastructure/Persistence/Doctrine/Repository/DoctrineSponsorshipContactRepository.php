<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Sponsorship\Contact\Entity\SponsorshipContact;
use App\Domain\Sponsorship\Contact\Repository\SponsorshipContactRepositoryInterface;
use App\Entity\SponsorshipContact as OrmSponsorshipContact;
use App\Infrastructure\Persistence\Doctrine\Mapper\SponsorshipContactMapper;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineSponsorshipContactRepository implements SponsorshipContactRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private SponsorshipContactMapper $mapper,
    ) {
    }

    public function save(SponsorshipContact $contact): void
    {
        $existing = $this->em->getRepository(OrmSponsorshipContact::class)->find($contact->id());
        $orm = $this->mapper->toOrm($contact, $existing);

        $this->em->persist($orm);
        $this->em->flush();
    }

    public function findAll(): array
    {
        $orms = $this->em->getRepository(OrmSponsorshipContact::class)->findBy([], ['createdAt' => 'DESC']);

        return array_map(fn (OrmSponsorshipContact $orm) => $this->mapper->toDomain($orm), $orms);
    }
}
